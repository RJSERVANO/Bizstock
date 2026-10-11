<?php
declare(strict_types=1);

namespace BizStock\Http\Controllers;

use BizStock\Http\Response;
use BizStock\Support\{Config,Ui};
use BizStock\Services\AuthService;
use BizStock\Http\HttpException;

final readonly class FoundationController
{
    public function __construct(private Config $config, private ?AuthService $auth=null) {}

    public function home(): Response
    {
        $config = $this->config;
        $ui = new Ui($config);
        $destination = '/login'; $cta = 'Sign in';
        // Anonymous public visits never connect to the database or issue session cookies.
        if ($this->auth && isset($_COOKIE['bizstock_session'])) {
            try {
                $user=$this->auth->current(true);
                $destination=$user['must_change_password']?'/account':'/dashboard';
                $cta=$user['must_change_password']?'Complete password change':'Open dashboard';
            } catch (HttpException $error) {
                if (!in_array($error->status,[401,403],true)) { throw $error; }
            }
        }
        ob_start();
        try {
            require $config->root . '/templates/pages/home.php';
            return new Response(200, (string) ob_get_contents(), ['Content-Type' => 'text/html; charset=utf-8','Cache-Control'=>'private, no-store']);
        } finally {
            ob_end_clean();
        }
    }

    public function health(): Response
    {
        // Liveness only. No DB connection or invented readiness result.
        return Response::success(['status'=>'ok']);
    }
}
