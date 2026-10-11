<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;

use BizStock\Http\{Response,HttpException,Authorization};
use BizStock\Services\AuthService;
use BizStock\Support\{ApiInput,SessionManager,Config,Ui};

final readonly class AuthController
{
    public function __construct(private AuthService $auth, private SessionManager $sessions, private Config $config) {}

    private function password(mixed $value): string
    {
        if (!is_string($value) || strlen($value)>72 || str_contains($value,"\0") || !mb_check_encoding($value,'UTF-8')) {
            throw new HttpException(422,'VALIDATION_ERROR','Invalid password input.');
        }
        return $value;
    }

    public function handle(string $path, string $requestId): Response
    {
        if ($path==='/api/v1/auth/csrf') { return Response::success(['csrf_token'=>$this->sessions->csrf()]); }
        if ($path==='/api/v1/auth/me') { return Response::success(Authorization::user($this->auth->current())); }
        $this->sessions->verifyCsrf();
        if ($path==='/api/v1/auth/login') {
            $body=ApiInput::body(['username','password']);
            $username=ApiInput::text($body['username']??null,'username',50);
            if (!preg_match('/^[A-Za-z0-9._-]{1,50}$/D',$username)) {
                throw new HttpException(422,'VALIDATION_ERROR','Enter a valid username.');
            }
            return Response::success(Authorization::user($this->auth->login(strtolower($username),$this->password($body['password']??null))));
        }
        $this->auth->current(true);
        if ($path==='/api/v1/auth/logout') {
            if ((int)($_SERVER['CONTENT_LENGTH']??0)>0) { ApiInput::body([]); }
            $this->sessions->logout(); return Response::success(['logged_out'=>true]);
        }
        $body=ApiInput::body(['current_password','new_password']);
        $changed=Authorization::user($this->auth->changePassword($this->password($body['current_password']??null),$this->password($body['new_password']??null),$requestId));
        $_SESSION['password_change_notice']=true;
        return Response::success($changed);
    }

    public function page(string $screen,string $roleHint=''): Response
    {
        $user=null;
        if ($screen==='account') {
            try { $user=Authorization::user($this->auth->current(true)); }
            catch (HttpException $error) { if($error->status!==401){throw $error;} return new Response(303,'',['Location'=>$this->config->url('/login'),'Cache-Control'=>'no-store']); }
        }
        $passwordChanged=$screen==='account' && !empty($_SESSION['password_change_notice']);
        if($screen==='account')unset($_SESSION['password_change_notice']);
        $config=$this->config; $ui=new Ui($config);
        ob_start();
        try { $title='Your account'; require $config->root.($screen==='account' && !$user['must_change_password']?'/templates/layouts/business.php':'/templates/layouts/auth.php'); $html=(string)ob_get_contents(); }
        finally { ob_end_clean(); }
        return new Response(200,$html,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
    }
}
