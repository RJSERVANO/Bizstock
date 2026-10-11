<?php
declare(strict_types=1);
namespace BizStock\Support;

use PDO;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo, private readonly string $directory) {}

    /** SQL files contain only simple statements; no routines, delimiters or semicolons in literals. */
    public function run(?string $through = null): array
    {
        if ($this->pdo->inTransaction()) { throw new RuntimeException('Migrations require a dedicated connection outside transactions.'); }
        $lock = 'bizstock:migrate:' . substr(hash('sha256', (string)$this->pdo->query('SELECT DATABASE()')->fetchColumn()),0,40);
        $statement=$this->pdo->prepare('SELECT GET_LOCK(?,5)'); $statement->execute([$lock]);
        if ((int)$statement->fetchColumn()!==1) { throw new RuntimeException('Migration lock unavailable.'); }
        try {
            $files=glob($this->directory.'/*.sql'); sort($files,SORT_STRING);
            if (!$files) { throw new RuntimeException('No migration files found.'); }
            $definitions=[];
            foreach($files as $file) {
                $id=basename($file);
                if (!preg_match('/^\d{3}_[a-z0-9_]+\.sql$/D',$id)) { throw new RuntimeException('Invalid migration identifier.'); }
                $definitions[$id]=['sql'=>file_get_contents($file),'checksum'=>hash_file('sha256',$file)];
            }
            if ($through!==null && !isset($definitions[$through])) { throw new RuntimeException('Unknown migration boundary.'); }
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (migration_id VARCHAR(100) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY, checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, status VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, completed_statements INT NOT NULL DEFAULT 0, started_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), applied_at DATETIME(6) NULL, CONSTRAINT ck_migrations_status CHECK(status IN ('running','applied')), CONSTRAINT ck_migrations_steps CHECK(completed_statements>=0)) ENGINE=InnoDB");
            $applied=$this->pdo->query('SELECT * FROM schema_migrations ORDER BY migration_id')->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
            // Validate every historical checksum before executing any new DDL.
            foreach($applied as $id=>$row) {
                if (!isset($definitions[$id]) || !hash_equals($row['checksum'],$definitions[$id]['checksum'])) { throw new RuntimeException('Missing or changed applied migration; restore original files.'); }
                if ($row['status']!=='applied') { throw new RuntimeException('Incomplete migration detected; review committed DDL and restore/recover explicitly.'); }
            }
            $result=[]; $seenPending=false;
            foreach($definitions as $id=>$definition) {
                if (isset($applied[$id])) {
                    if ($seenPending) { throw new RuntimeException('Migration history is out of order.'); }
                } else { $seenPending=true; }
            }
            foreach($definitions as $id=>$definition) {
                if (isset($applied[$id])) { $result[$id]='already applied'; }
                else {
                    // Refuse an unmanaged installation before baseline DDL can touch it.
                    if ($id===array_key_first($definitions)) {
                        $count=$this->pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('users','categories','suppliers','products','transactions','operation_requests','audit_events','rate_limits','security_locks')")->fetchColumn();
                        if ((int)$count>0) { throw new RuntimeException('Untracked existing schema; explicit import/upgrade required. No existing tables changed.'); }
                    }
                    $insert=$this->pdo->prepare("INSERT INTO schema_migrations(migration_id,checksum,status) VALUES (?,?,'running')"); $insert->execute([$id,$definition['checksum']]);
                    $steps=0;
                    foreach(explode(';',$definition['sql']) as $sql) {
                        if (trim($sql)==='') { continue; }
                        $this->pdo->exec($sql); $steps++;
                        $update=$this->pdo->prepare('UPDATE schema_migrations SET completed_statements=? WHERE migration_id=?'); $update->execute([$steps,$id]);
                    }
                    $update=$this->pdo->prepare("UPDATE schema_migrations SET status='applied',applied_at=CURRENT_TIMESTAMP(6) WHERE migration_id=?"); $update->execute([$id]);
                    $result[$id]='applied';
                }
                if ($through===$id) { break; }
            }
            return $result;
        } finally {
            $release=$this->pdo->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lock]);
        }
    }
}
