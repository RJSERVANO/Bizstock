<?php
declare(strict_types=1);namespace BizStock\Http\Controllers;
use BizStock\Support\{PdoFactory,SessionManager,ApiInput};use BizStock\Services\{AuthService,StockCountService};use BizStock\Http\{Authorization,Response};
final readonly class StockCountController
{
 public function __construct(private PdoFactory $factory,private AuthService $auth,private SessionManager $sessions){}
 public function handle(string $method,?int $id,?int $product,?string $action,string $uri,string $request):Response{
  $actor=$this->auth->current();Authorization::requireRole($actor,$action==='approve'?['Admin']:['Admin','Staff']);$query=ApiInput::query(explode('?',$uri,2)[1]??'',$method==='GET'&&$id===null?['status','page','per_page','sort','direction']:[]);$service=new StockCountService($this->factory->connection());if($method==='GET'){$r=$service->read($id,$query,$actor['role']);return Response::success($r['data'],$r['meta']);}$this->sessions->verifyCsrf();$key=$_SERVER['HTTP_IDEMPOTENCY_KEY']??'';
  if($id===null)$result=$service->create(ApiInput::body(['note']),$actor,$key,$request);elseif($product!==null)$result=$service->saveLine($id,$product,ApiInput::body(['expected_version','counted_quantity','reason','recount','expected_stock_revision']),$actor,$request);else $result=$service->transition($id,$action??'',ApiInput::body(['expected_version']),$actor,$key,$request);return Response::success($result,[],$id===null||$action==='approve'?201:200);
 }
}
