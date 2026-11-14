<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Http\{Response,Authorization,HttpException};
use BizStock\Services\AuthService;
use BizStock\Support\{Config,Ui};
final readonly class BusinessPageController
{
 public function __construct(private Config $config,private AuthService $auth){}
 public function page(string $screen,?int $id=null,string $uri=''): Response
 {
  try{$user=Authorization::user($this->auth->current());}catch(HttpException $e){if($e->status===401||$e->errorCode==='PASSWORD_CHANGE_REQUIRED'){return new Response(303,'',['Location'=>$this->config->url($e->status===401?'/login':'/account')]);}throw $e;}
  Authorization::requireRole($user,in_array($screen,['product-form','users','audit-events'],true)?['Admin']:(in_array($screen,['products','dashboard','help'],true)?['Admin','Staff','Viewer']:['Admin','Staff']));
  $listQuery='';$stateNotice='';$returnTo=$this->config->url('/products');
  if($screen==='products')try{$listQuery=\BizStock\Support\ProductListState::query(explode('?',$uri,2)[1]??'',$user['role']);}catch(HttpException){$stateNotice='Invalid inventory filters were cleared. Choose filters and search again.';}
  if($screen==='product-form')try{$parameters=\BizStock\Support\ApiInput::query(explode('?',$uri,2)[1]??'',['return_to']);$returnTo=\BizStock\Support\ProductListState::destination($parameters['return_to']??'',$this->config,$user['role']);}catch(HttpException){$stateNotice='Invalid return destination ignored.';}
  $config=$this->config;$ui=new Ui($config);$title=match($screen){'analytics'=>'Movement analytics','audit-events'=>'Audit log','stock-counts'=>'Physical stock counts','movements'=>'Stock movements','products'=>'Products','product-form'=>$id?'Edit product':'Add product',default=>ucfirst($screen)};
  ob_start();try{require $config->root.'/templates/layouts/business.php';$html=(string)ob_get_contents();}finally{ob_end_clean();}
  return new Response(200,$html,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
 }
}
