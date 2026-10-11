<?php
declare(strict_types=1);
namespace BizStock\Services;

use BizStock\Repositories\RateLimitRepository;
use BizStock\Http\HttpException;

final readonly class LoginRateLimiter
{
    public function __construct(private \PDO $pdo) {}
    public function consume(string $username, string $peer): void
    {
        // Always peer before account: separate namespaces make the lock order acyclic.
        // A blocked peer cannot create unlimited new account buckets.
        $buckets=[hash('sha256','peer|'.$peer)=>30,hash('sha256','account|'.$username)=>5];
        $retry=0;
        $this->pdo->beginTransaction();
        try {
            $repository=new RateLimitRepository($this->pdo);
            foreach ($buckets as $key=>$limit) { $retry=$repository->consume($key,$limit); if($retry>0){break;} }
            $this->pdo->commit();
        } catch (\Throwable $error) { if($this->pdo->inTransaction()){$this->pdo->rollBack();} throw $error; }
        if ($retry>0) { throw new HttpException(429,'RATE_LIMITED','Too many sign-in attempts. Try again later.',[],['Retry-After'=>(string)$retry]); }
    }
}
