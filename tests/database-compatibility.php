<?php
declare(strict_types=1);

use BizStock\Support\DatabasePlatform;
use BizStock\Support\CanonicalJson;
use BizStock\Support\PdoFactory;

$config = require dirname(__DIR__) . '/config/bootstrap.php';
if (!in_array($config->environment, ['local', 'test'], true)) {
    throw new RuntimeException('Compatibility probes never run in production.');
}
$factory = new PdoFactory($config);
$pdo = $factory->connection();
$prefix = 'compat_' . bin2hex(random_bytes(6));
$tables = [$prefix . '_parent', $prefix . '_child', $prefix . '_ledger'];
[$parent, $child, $ledger] = $tables;
$checks = [];
$check = static function (bool $ok, string $name) use (&$checks): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $name); }
    $checks[] = $name;
};
$reject = static function (callable $run, string $code, string $name) use ($check): void {
    try { $run(); } catch (PDOException $error) {
        $check(DatabasePlatform::failureCode($error) === $code, $name);
        return;
    }
    throw new RuntimeException('FAIL: accepted ' . $name);
};
$worker = null;
try {
    $check(!$pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES), 'native prepared statements');
    $check($factory->connection() === $pdo, 'single cached connection');
    $server = $pdo->query('SELECT VERSION() AS version, @@version_comment AS engine, @@session.time_zone AS timezone, @@session.sql_mode AS sql_mode, @@session.collation_connection AS collation, @@session.foreign_key_checks AS foreign_keys')->fetch();
    $check($server['timezone'] === '+00:00' && (int) $server['foreign_keys'] === 1, 'UTC and FK enforcement');
    $check(str_contains($server['sql_mode'], 'STRICT_TRANS_TABLES') && str_contains($server['sql_mode'], 'ONLY_FULL_GROUP_BY'), 'strict SQL and grouping');
    if ($factory->platform()->engine === 'mariadb') {
        $pdo->exec('SET SESSION check_constraint_checks=OFF');
        $pdo->exec('SET SESSION foreign_key_checks=OFF');
        $factory->platform()->configure($pdo);
        $check((int) $pdo->query('SELECT @@session.foreign_key_checks')->fetchColumn() === 1, 'session configuration restores FK checks');
        $check((int) $pdo->query('SELECT @@session.check_constraint_checks')->fetchColumn() === 1, 'MariaDB CHECK enforcement');
    }
    $pdo->exec("CREATE TABLE `{$parent}` (id INT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $json = DatabasePlatform::jsonColumn('payload', $prefix . '_ck_json');
    $pdo->exec("CREATE TABLE `{$child}` (id INT PRIMARY KEY, parent_id INT NOT NULL, actor INT NOT NULL, operation_key VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, sku VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE, role VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, movement_type VARCHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL, quantity INT NOT NULL, price DECIMAL(10,2) NOT NULL, correction_id INT NULL UNIQUE, created_at DATETIME(6) NOT NULL, {$json}, UNIQUE(actor,operation_key), CONSTRAINT `{$prefix}_fk` FOREIGN KEY(parent_id) REFERENCES `{$parent}`(id) ON DELETE RESTRICT, CONSTRAINT `{$prefix}_ck_qty` CHECK(quantity>0), CONSTRAINT `{$prefix}_ck_price` CHECK(price>=0), CONSTRAINT `{$prefix}_ck_role` CHECK(role IN ('Admin','Staff','Viewer')), CONSTRAINT `{$prefix}_ck_type` CHECK(movement_type IN ('IN','OUT'))) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE `{$ledger}` (id INT PRIMARY KEY, quantity INT NOT NULL, version INT NOT NULL DEFAULT 1, CONSTRAINT `{$prefix}_ck_stock` CHECK(quantity>=0)) ENGINE=InnoDB");
    $check((string) $pdo->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$child}'")->fetchColumn() === 'InnoDB', 'fixture engine is InnoDB');
    $insert = $pdo->prepare("INSERT INTO `{$parent}` VALUES (?,?)");
    $insert->execute([1, 'Café']);
    $reject(fn () => $insert->execute([2, 'CAFÉ']), 'duplicate', 'Unicode/case category uniqueness');
    $reject(fn () => $insert->execute([2, 'Café ']), 'duplicate', 'trailing-space category uniqueness');
    $stmt = $pdo->prepare("INSERT INTO `{$child}` (id,parent_id,actor,operation_key,sku,role,movement_type,quantity,price,correction_id,created_at,payload) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $document = CanonicalJson::encode(['price' => '245.00', 'notes' => 'Unicode ✓', 'qty' => 1]);
    $baseline = [1,1,1,'op1','SKU-1','Admin','IN',1,'245.00',null,'2026-10-11 01:02:03.123456',$document];
    $stmt->execute($baseline);
    $second = $baseline; $second[0]=2; $second[3]='op2'; $second[4]='SKU-2'; $stmt->execute($second);
    $check((int) $pdo->query("SELECT COUNT(*) FROM `{$child}` WHERE correction_id IS NULL")->fetchColumn() === 2, 'multiple NULL correction links');
    $read = $pdo->query("SELECT price, created_at, payload FROM `{$child}` WHERE id=1")->fetch();
    $check($read['price'] === '245.00', 'DECIMAL returned losslessly as string');
    $check($read['created_at'] === $baseline[10], 'UTC microsecond timestamp roundtrip');
    $check($read['payload'] === $document && json_decode($read['payload'], true)['notes'] === 'Unicode ✓', 'portable JSON and Unicode roundtrip');
    foreach ([
        [1,999,'reference','missing parent rejected'], [7,0,'constraint','nonpositive movement rejected'],
        [8,'-0.01','constraint','negative price rejected'], [5,'Owner','constraint','invalid role rejected'],
        [6,'BAD','constraint','invalid movement type rejected'], [11,'{bad','constraint','invalid JSON rejected'],
        [6,'EDIT','range','movement type overflow rejected'],
        [5,'admin','constraint','role case variants rejected'], [6,'in','constraint','movement type case variants rejected'],
        [7,'2147483648','range','integer overflow rejected'], [8,'100000000.00','range','decimal overflow rejected'],
    ] as [$index,$value,$code,$label]) {
        $test=$baseline; $test[0]=3; $test[3]='op3'; $test[4]='SKU-3'; $test[$index]=$value;
        $reject(fn () => $stmt->execute($test), $code, $label);
    }
    $test=$baseline; $test[0]=3; $test[4]='SKU-3';
    $reject(fn () => $stmt->execute($test), 'duplicate', 'actor-scoped operation key prevents duplicate');
    $test[2]=2; $stmt->execute($test);
    $check(true, 'same operation key for different actor allowed');
    $pdo->exec("UPDATE `{$child}` SET correction_id=1 WHERE id=2");
    $reject(fn () => $pdo->exec("UPDATE `{$child}` SET correction_id=1 WHERE id=3"), 'duplicate', 'unique non-null reversal link');
    $reject(fn () => $pdo->exec("DELETE FROM `{$parent}` WHERE id=1"), 'reference', 'referenced parent deletion restricted');
    $lookup=$pdo->prepare("SELECT COUNT(*) FROM `{$child}` WHERE sku=?"); $lookup->execute(['sku-1']);
    $check((int) $lookup->fetchColumn() === 0, 'binary canonical SKU comparison');
    $lookup->execute(["SKU-1' OR 1=1 --"]);
    $check((int) $lookup->fetchColumn() === 0, 'native prepared values cannot inject SQL');
    $pdo->exec("INSERT INTO `{$ledger}` VALUES (1,5,1)");
    $reject(fn () => $pdo->exec("UPDATE `{$ledger}` SET quantity=-1 WHERE id=1"), 'constraint', 'negative balance rejected');
    $pdo->beginTransaction(); $pdo->exec("UPDATE `{$ledger}` SET quantity=1 WHERE id=1");
    try { $pdo->exec("UPDATE `{$child}` SET payload='invalid' WHERE id=1"); } catch (PDOException) { $pdo->rollBack(); }
    $check((int) $pdo->query("SELECT quantity FROM `{$ledger}` WHERE id=1")->fetchColumn() === 5, 'multi-write failure rolls back balance');
    $pdo->exec("UPDATE `{$ledger}` SET version=version+1 WHERE id=1 AND version=1");
    $check($pdo->exec("UPDATE `{$ledger}` SET quantity=0 WHERE id=1 AND version=1") === 0, 'stale conditional update does not overwrite');
    // Verify a separate process really waits for a row lock, then sees committed balance.
    $pdo->beginTransaction(); $pdo->query("SELECT quantity FROM `{$ledger}` WHERE id=1 FOR UPDATE")->fetchColumn();
    $pdo->exec("UPDATE `{$ledger}` SET quantity=1 WHERE id=1");
    $ready=$config->root . '/var/' . $prefix . '.ready'; $result=$config->root . '/var/' . $prefix . '.result';
    $worker=proc_open([PHP_BINARY, __DIR__ . '/database-worker.php', $ledger, $ready, $result],
        [0=>['pipe','r'],1=>['file',$config->root.'/var/db-worker.log','a'],2=>['file',$config->root.'/var/db-worker.log','a']],$pipes,$config->root);
    if (!is_resource($worker)) { throw new RuntimeException('Cannot start lock-test worker.'); }
    fclose($pipes[0]);
    for($i=0;$i<50 && !is_file($ready);$i++){usleep(100000);clearstatcache();}
    $check(is_file($ready), 'independent worker connected');
    usleep(300000); clearstatcache();
    $check(!is_file($result), 'independent connection blocks on FOR UPDATE');
    $pdo->commit();
    for($i=0;$i<50 && !is_file($result);$i++){usleep(100000);clearstatcache();}
    $check(is_file($result) && file_get_contents($result)==='insufficient', 'competing stock probe rejects oversell after commit');
    $check(proc_close($worker)===0, 'lock worker completed'); $worker=null;
    $check((int)$pdo->query("SELECT quantity FROM `{$ledger}` WHERE id=1")->fetchColumn()===1, 'competing probe balance unchanged');
    // Exercise engine-specific lock timeout classification on a fresh separate connection.
    $other=(new PdoFactory($config))->connection(); $other->exec('SET SESSION innodb_lock_wait_timeout=1');
    $pdo->beginTransaction(); $pdo->query("SELECT quantity FROM `{$ledger}` WHERE id=1 FOR UPDATE")->fetchColumn();
    $other->beginTransaction();
    $reject(fn () => $other->exec("UPDATE `{$ledger}` SET quantity=0 WHERE id=1"), 'lock_timeout', 'lock timeout mapped safely');
    $other->rollBack(); $pdo->rollBack();
    $check((int)$pdo->query("SELECT quantity FROM `{$ledger}` WHERE id=1")->fetchColumn()===1, 'timeout rollback preserves balance');
    $report=['verified_at_utc'=>gmdate('c'),'target'=>$config->dbTarget,'scope'=>'compatibility probes only; not application/service acceptance',
        'server'=>$server,'assertions'=>count($checks),'passed'=>$checks,
        'targets'=>[$config->dbTarget=>'capability probes passed',
            $config->dbTarget==='mysql84'?'mariadb104-local':'mysql84'=>'pending; not tested by this run']];
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    if (isset($other) && $other->inTransaction()) { $other->rollBack(); }
    if (is_resource($worker)) { proc_terminate($worker); proc_close($worker); }
    foreach(array_reverse($tables) as $table){ $pdo->exec("DROP TABLE IF EXISTS `{$table}`"); }
    foreach ([$ready ?? null, $result ?? null] as $file) { if ($file !== null && is_file($file)) { unlink($file); } }
}
file_put_contents($config->root.'/docs/database-compatibility-results.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo 'PASS: '.count($checks).' actual '.$factory->platform()->engine.' connection/constraint/transaction assertions. Probe tables cleaned; no application data changed.'.PHP_EOL;
