<?php
declare(strict_types=1);
namespace BizStock\Repositories;

use BizStock\Support\CanonicalJson;

final readonly class AuditRepository
{
    public function __construct(private \PDO $pdo) {}
    public function record(int $actor, string $action, string $entity, int $id, ?array $before, ?array $after, string $requestId): void
    {
        $query=$this->pdo->prepare('INSERT INTO audit_events(actor_id,action,entity_type,entity_id,before_json,after_json,request_id) VALUES (?,?,?,?,?,?,?)');
        $query->execute([$actor,$action,$entity,$id,$before===null?null:CanonicalJson::encode($before),$after===null?null:CanonicalJson::encode($after),$requestId]);
    }
}
