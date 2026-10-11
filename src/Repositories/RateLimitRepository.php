<?php
declare(strict_types=1);
namespace BizStock\Repositories;

final readonly class RateLimitRepository
{
    public function __construct(private \PDO $pdo) {}

    // The caller owns one transaction and consistent peer-before-account locking.
    public function consume(string $key, int $limit): int
    {
        $insert=$this->pdo->prepare('INSERT INTO rate_limits(limiter_key,window_started_at,reset_at,attempts) VALUES (?,UTC_TIMESTAMP(6),DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 5 MINUTE),0) ON DUPLICATE KEY UPDATE reset_at=LEAST(reset_at,DATE_ADD(window_started_at,INTERVAL 5 MINUTE))');
        $insert->execute([$key]);
        $select=$this->pdo->prepare('SELECT attempts, GREATEST(0,CEIL(TIMESTAMPDIFF(MICROSECOND,UTC_TIMESTAMP(6),reset_at)/1000000)) AS remaining FROM rate_limits WHERE limiter_key=? FOR UPDATE');
        $select->execute([$key]); $row=$select->fetch();
        if ((int)$row['remaining']===0) {
            $reset=$this->pdo->prepare('UPDATE rate_limits SET attempts=0,window_started_at=UTC_TIMESTAMP(6),reset_at=DATE_ADD(UTC_TIMESTAMP(6),INTERVAL 5 MINUTE) WHERE limiter_key=?');
            $reset->execute([$key]); $row=['attempts'=>0,'remaining'=>300];
        }
        if ((int)$row['attempts']>=$limit) { return (int)$row['remaining']; }
        $update=$this->pdo->prepare('UPDATE rate_limits SET attempts=attempts+1 WHERE limiter_key=?');
        $update->execute([$key]); return 0;
    }
}
