<?php
declare(strict_types=1);
namespace BizStock\Services;

use BizStock\Http\HttpException;
use BizStock\Repositories\AuthRepository;
use BizStock\Support\{PdoFactory,SessionManager,PasswordPolicy};

final readonly class AuthService
{
    // Timing-only hash: never an account or usable credential.
    private const DUMMY_HASH='$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    public function __construct(private PdoFactory $factory, private SessionManager $sessions) {}

    public function current(bool $allowForced=false): array
    {
        $this->sessions->start();
        if (!isset($_SESSION['user_id'])) { throw new HttpException(401,'UNAUTHENTICATED','Sign in to continue.'); }
        $user=(new AuthRepository($this->factory->connection()))->byId((int)$_SESSION['user_id']);
        if (!$user || !(bool)$user['is_active'] || (int)$user['session_version']!==($_SESSION['session_version']??0)) {
            $this->sessions->reset(); throw new HttpException(401,'UNAUTHENTICATED','Sign in to continue.');
        }
        if (!in_array($user['role'],['Admin','Staff','Viewer'],true)) { throw new HttpException(403,'FORBIDDEN','Access denied.'); }
        if (!$allowForced && (bool)$user['must_change_password']) { throw new HttpException(403,'PASSWORD_CHANGE_REQUIRED','Change your password to continue.'); }
        $_SESSION['last_seen']=time(); return $user;
    }

    public function login(string $username, string $password): array
    {
        $pdo=$this->factory->connection();
        (new LoginRateLimiter($pdo))->consume($username,(string)($_SERVER['REMOTE_ADDR']??'unknown'));
        $repository=new AuthRepository($pdo);
        $user=$repository->byUsername($username);
        $valid=password_verify($password,$user['password_hash']??self::DUMMY_HASH);
        if (!$valid || !$user || !(bool)$user['is_active']) { throw new HttpException(401,'INVALID_CREDENTIALS','Invalid username or password.'); }
        // Lock and recheck to avoid issuing a session from a concurrent reset/deactivation.
        $pdo->beginTransaction();
        try {
            $locked=$repository->byId((int)$user['user_id'],true);
            if (!$locked || !$locked['is_active'] || $locked['password_hash']!==$user['password_hash']
                || $locked['session_version']!==$user['session_version']) { throw new HttpException(401,'INVALID_CREDENTIALS','Invalid username or password.'); }
            $pdo->commit();
        } catch (\Throwable $error) { if($pdo->inTransaction()){$pdo->rollBack();} throw $error; }
        $this->sessions->authenticate($locked); return $locked;
    }

    public function changePassword(string $current, string $next, string $requestId): array
    {
        $user=$this->current(true);
        (new LoginRateLimiter($this->factory->connection()))->consume('password-change|'.$user['user_id'],
            'password-change|'.(string)($_SERVER['REMOTE_ADDR']??'unknown'));
        try { $hash=PasswordPolicy::hash($next); }
        catch (\InvalidArgumentException) { throw new HttpException(422,'VALIDATION_ERROR','Use a password of 12–72 UTF-8 bytes without NUL.'); }
        $pdo=$this->factory->connection(); $repository=new AuthRepository($pdo);
        $pdo->beginTransaction();
        try {
            $locked=$repository->byId((int)$user['user_id'],true);
            if (!$locked || !$locked['is_active'] || (int)$locked['session_version']!==(int)$user['session_version']) {
                throw new HttpException(401,'UNAUTHENTICATED','Sign in to continue.');
            }
            if (!password_verify($current,$locked['password_hash'])) { throw new HttpException(403,'CURRENT_PASSWORD_INVALID','Current password is incorrect.'); }
            if (password_verify($next,$locked['password_hash'])) { throw new HttpException(422,'VALIDATION_ERROR','Choose a different password.'); }
            $repository->changePassword((int)$locked['user_id'],$hash,(int)$locked['session_version'],$requestId);
            $updated=$repository->byId((int)$locked['user_id']);
            $pdo->commit();
        } catch (\Throwable $error) { if($pdo->inTransaction()){$pdo->rollBack();} throw $error; }
        $this->sessions->authenticate($updated,true); return $updated;
    }
}
