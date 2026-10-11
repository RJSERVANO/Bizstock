<?php
declare(strict_types=1);namespace BizStock\Http\Controllers;
use BizStock\Support\{PdoFactory,SessionManager,ApiInput};use BizStock\Services\{AuthService,UserService};use BizStock\Repositories\{UserRepository,AuditLogRepository};use BizStock\Http\{Authorization,Response};
final readonly class UserController{
 public function __construct(private PdoFactory $factory,private AuthService $auth,private SessionManager $sessions){}
 public function handle(string $method,?int $id,string $uri,string $requestId,bool $audit=false,bool $reset=false):Response{
  $actor=$this->auth->current();Authorization::requireRole($actor,['Admin']);$query=ApiInput::query(explode('?',$uri,2)[1]??'',$method==='GET'?($audit?['entity_type','entity_id','actor_id','date_from','date_to','page','per_page','sort','direction']:['q','is_active','page','per_page','sort','direction']):[]);
  if($method==='GET'){$r=$audit?(new AuditLogRepository($this->factory->connection()))->listing($query):(new UserRepository($this->factory->connection()))->listing($query);return Response::success($r['data'],$r['meta']);}
  $this->sessions->verifyCsrf();$body=ApiInput::body($reset?['new_password']:($method==='DELETE'?[]:($method==='POST'?['username','full_name','role','password']:['username','full_name','role','is_active'])));return Response::success((new UserService($this->factory->connection()))->write($method,$id,$body,$actor,$requestId,$reset),[],$method==='POST'&&!$reset?201:200);
 }
}
