<?php
declare(strict_types=1);
namespace BizStock\Repositories;

final readonly class AuthRepository
{
    public function __construct(private \PDO $pdo) {}

    public function byUsername(string $username): ?array
    {
        $query=$this->pdo->prepare('SELECT * FROM users WHERE username=?');
        $query->execute([$username]); return $query->fetch()?:null;
    }

    public function byId(int $id, bool $lock=false): ?array
    {
        $query=$this->pdo->prepare('SELECT * FROM users WHERE user_id=?'.($lock?' FOR UPDATE':''));
        $query->execute([$id]); return $query->fetch()?:null;
    }

    public function updateName(int $id,string $name): void
    {
        $query=$this->pdo->prepare('UPDATE users SET full_name=?,updated_at=UTC_TIMESTAMP(6) WHERE user_id=?');
        $query->execute([$name,$id]);
    }

    public function changePassword(int $id, string $hash, int $version, string $requestId): void
    {
        $query=$this->pdo->prepare('UPDATE users SET password_hash=?,session_version=session_version+1,must_change_password=0,updated_at=UTC_TIMESTAMP(6) WHERE user_id=?');
        $query->execute([$hash,$id]);
        $query=$this->pdo->prepare("INSERT INTO audit_events(actor_id,action,entity_type,entity_id,before_json,after_json,request_id) VALUES (?, 'account.password_changed', 'user', ?, ?, ?, ?)");
        $query->execute([$id,$id,json_encode(['session_version'=>$version],JSON_THROW_ON_ERROR),
            json_encode(['session_version'=>$version+1,'must_change_password'=>false],JSON_THROW_ON_ERROR),$requestId]);
    }
}
