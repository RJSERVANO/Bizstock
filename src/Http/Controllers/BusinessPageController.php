<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Http\{Response,Authorization,HttpException};
use BizStock\Services\AuthService;
use BizStock\Support\{Config,Ui};
final readonly class BusinessPageController
{
 public function __construct(private Config $config,private AuthService $auth){}
 public function page(string $screen,?int $id=null): Response
 {
  try{$user=Authorization::user($this->auth->current());}catch(HttpException $e){if($e->status===401||$e->errorCode==='PASSWORD_CHANGE_REQUIRED'){return new Response(303,'',['Location'=>$this->config->url($e->status===401?'/login':'/account')]);}throw $e;}
  Authorization::requireRole($user,in_array($screen,['product-form','users','audit-events'],true)?['Admin']:(in_array($screen,['products','dashboard'],true)?['Admin','Staff','Viewer']:['Admin','Staff']));
  $config=$this->config;$ui=new Ui($config);$title=match($screen){'audit-events'=>'Audit log','movements'=>'Stock movements','products'=>'Products','product-form'=>$id?'Edit product':'Add product',default=>ucfirst($screen)};
  ob_start();try{require $config->root.'/templates/layouts/business.php';$html=(string)ob_get_contents();}finally{ob_end_clean();}
  return new Response(200,$html,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
 }
}
