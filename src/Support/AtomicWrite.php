<?php
declare(strict_types=1);
namespace BizStock\Support;
use BizStock\Http\{HttpException,Authorization};
use BizStock\Repositories\AuthRepository;

final class AtomicWrite
{
    public static function run(\PDO $pdo,array $actor,array $roles,callable $work,bool $securityLock=false): mixed
    {
        if($pdo->inTransaction()){throw new \LogicException('AtomicWrite owns the transaction.');}
        for($attempt=0;$attempt<3;$attempt++) {
            $pdo->beginTransaction();
            try {
                if($securityLock && (int)$pdo->query('SELECT lock_id FROM security_locks WHERE lock_id=1 FOR UPDATE')->fetchColumn()!==1){throw new \RuntimeException('Security sentinel missing.');}
                $user=(new AuthRepository($pdo))->byId((int)$actor['user_id'],true);
                if(!$user || !$user['is_active'] || (int)$user['session_version']!==(int)$actor['session_version']) {throw new HttpException(401,'UNAUTHENTICATED','Sign in to continue.');}
                Authorization::requireRole($user,$roles);
                if($user['must_change_password']){throw new HttpException(403,'PASSWORD_CHANGE_REQUIRED','Change your password to continue.');}
                $result=$work($user);$pdo->commit();return $result;
            }catch(\Throwable $error){
                if($pdo->inTransaction()){$pdo->rollBack();}
                if($error instanceof \PDOException && in_array(DatabasePlatform::failureCode($error),['deadlock','lock_timeout'],true)) {
                    if($attempt===2){throw new HttpException(503,'SERVICE_UNAVAILABLE','Stock is busy. Retry with the same operation key.',[],['Retry-After'=>'1']);}
                    usleep(random_int(20000,60000)*($attempt+1));continue;
                }
                throw $error;
            }
        }
        throw new \LogicException('Unreachable transaction state.');
    }
}
