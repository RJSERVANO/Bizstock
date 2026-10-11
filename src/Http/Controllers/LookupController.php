<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;

use BizStock\Http\{Response,Authorization,HttpException};
use BizStock\Services\{AuthService,LookupService};
use BizStock\Support\{SessionManager,Config,Ui,ApiInput};

final readonly class LookupController
{
    public function __construct(private AuthService $auth,private SessionManager $sessions,private LookupService $service,private Config $config) {}
    public function handle(string $resource,string $method,?int $id,string $uri,string $requestId): Response
    {
        $actor=$this->auth->current();
        Authorization::requireRole($actor,$method==='GET'?($resource==='categories'?['Admin','Staff','Viewer']:['Admin','Staff']):['Admin']);
        $query=ApiInput::query(explode('?',$uri,2)[1]??'',$method==='GET'?['q','page','per_page','sort','direction']:[]);
        if($method==='GET') { $result=$this->service->listing($resource,$query); return Response::success($result['data'],$result['meta']); }
        $this->sessions->verifyCsrf();
        $body=$method==='DELETE' && (int)($_SERVER['CONTENT_LENGTH']??0)===0?[]:ApiInput::body($method==='DELETE'?[]:LookupService::fields($resource));
        return Response::success($this->service->mutate($resource,$method,$id,$body,$actor,$requestId),[],$method==='POST'?201:200);
    }
    public function page(string $resource): Response
    {
        try { $user=Authorization::user($this->auth->current()); }
        catch(HttpException $error) {
            if($error->status===401){return new Response(303,'',['Location'=>$this->config->url('/login')]);}
            if($error->errorCode==='PASSWORD_CHANGE_REQUIRED'){return new Response(303,'',['Location'=>$this->config->url('/account')]);} throw $error;
        }
        Authorization::requireRole($user,$resource==='categories'?['Admin','Staff','Viewer']:['Admin','Staff']);
        $config=$this->config; $ui=new Ui($config); $title=ucfirst($resource);
        ob_start(); try { require $config->root.'/templates/layouts/lookups.php'; $html=(string)ob_get_contents(); } finally {ob_end_clean();}
        return new Response(200,$html,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
    }
}
