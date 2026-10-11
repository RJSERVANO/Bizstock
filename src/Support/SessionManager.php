<?php
declare(strict_types=1);
namespace BizStock\Support;

use BizStock\Http\HttpException;

final class SessionManager
{
    public function __construct(private readonly Config $config) {}

    public static function cookieOptions(Config $config): array
    {
        return ['lifetime'=>0, 'path'=>$config->basePath.'/', 'secure'=>$config->environment==='production',
            'httponly'=>true, 'samesite'=>'Lax'];
    }

    public function start(): void
    {
        if (session_status()===PHP_SESSION_ACTIVE) { return; }
        if ($this->config->environment==='production' && ($_SERVER['HTTPS']??'')!=='on' && ($_SERVER['HTTPS']??'')!=='1') {
            throw new HttpException(403,'HTTPS_REQUIRED','HTTPS is required.');
        }
        $directory=$this->config->root.'/var/sessions/'.hash('sha256',$this->config->environment.'|'.$this->config->dbName.'|'.$this->config->basePath);
        if (!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) { throw new \RuntimeException('Session storage unavailable.'); }
        session_save_path($directory);
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        ini_set('session.use_trans_sid','0');
        ini_set('session.gc_maxlifetime',(string)$this->config->sessionAbsoluteSeconds);
        session_name('bizstock_session');
        session_set_cookie_params(self::cookieOptions($this->config));
        session_start();
        if (isset($_SESSION['user_id']) && (time()-($_SESSION['last_seen']??0)>=$this->config->sessionIdleSeconds
            || time()-($_SESSION['authenticated_at']??0)>=$this->config->sessionAbsoluteSeconds)) {
            $this->reset();
        }
    }

    public function csrf(): string
    {
        $this->start();
        return $_SESSION['csrf']??=bin2hex(random_bytes(32));
    }

    public function verifyCsrf(): void
    {
        $expected=$this->csrf();
        $supplied=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
        if (!is_string($supplied) || !hash_equals($expected,$supplied)) {
            throw new HttpException(403,'CSRF_INVALID','Refresh the page and try again.');
        }
    }

    public function authenticate(array $user, bool $retainAge=false): void
    {
        $this->start();
        $born=$retainAge?($_SESSION['authenticated_at']??time()):time();
        session_regenerate_id(true);
        $_SESSION=['user_id'=>(int)$user['user_id'],'session_version'=>(int)$user['session_version'],
            'authenticated_at'=>$born,'last_seen'=>time(),'csrf'=>bin2hex(random_bytes(32))];
    }

    public function reset(): void
    {
        $_SESSION=[];
        session_regenerate_id(true);
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION=[];
        session_destroy();
        $options=self::cookieOptions($this->config); unset($options['lifetime']);
        setcookie('bizstock_session','',['expires'=>time()-3600]+$options);
    }
}
