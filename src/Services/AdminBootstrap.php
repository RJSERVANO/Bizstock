<?php
declare(strict_types=1);
namespace BizStock\Services;

use BizStock\Support\CanonicalJson;
use BizStock\Support\PasswordPolicy;
use PDO;
use RuntimeException;

final class AdminBootstrap
{
    public function __construct(private readonly PDO $pdo) {}
    public function create(string $username,string $name,string $password): int
    {
        $username=strtolower(trim($username)); $name=trim($name);
        if (!preg_match('/^[a-z0-9._-]{1,50}$/D',$username) || !mb_check_encoding($name,'UTF-8') || mb_strlen($name)<1 || mb_strlen($name)>100 || preg_match('/[\x00-\x1f\x7f]/',$name)) {
            throw new \InvalidArgumentException('Invalid Admin username or name.');
        }
        $hash=PasswordPolicy::hash($password);
        if ($this->pdo->inTransaction()) { throw new RuntimeException('Bootstrap owns its transaction.'); }
        $this->pdo->beginTransaction();
        try {
            if ((int)$this->pdo->query('SELECT lock_id FROM security_locks WHERE lock_id=1 FOR UPDATE')->fetchColumn()!==1) { throw new RuntimeException('Security sentinel missing.'); }
            if ((int)$this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()>0) { throw new RuntimeException('Initial bootstrap requires no existing accounts; use later account administration/recovery.'); }
            $insert=$this->pdo->prepare("INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,'Admin',1)"); $insert->execute([$name,$username,$hash]);
            $id=(int)$this->pdo->lastInsertId();
            $audit=$this->pdo->prepare("INSERT INTO audit_events(actor_id,action,entity_type,entity_id,after_json,request_id) VALUES (?,'admin.bootstrap','user',?,?,?)");
            $audit->execute([$id,$id,CanonicalJson::encode(['username'=>$username,'role'=>'Admin','must_change_password'=>true]),'cli-'.bin2hex(random_bytes(16))]);
            $this->pdo->commit(); return $id;
        } catch(\Throwable $error) { if ($this->pdo->inTransaction()){$this->pdo->rollBack();} throw $error; }
    }
}
