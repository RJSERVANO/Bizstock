<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Support\{ApiInput,PdoFactory,SessionManager};
use BizStock\Services\{AuthService,ProfileService};
use BizStock\Http\Response;
final readonly class ProfileController
{
 public function __construct(private PdoFactory $factory,private AuthService $auth,private SessionManager $sessions){}
 public function handle(string $uri,string $requestId): Response
 {
  $actor=$this->auth->current();$this->sessions->verifyCsrf();ApiInput::query(explode('?',$uri,2)[1]??'',[]);
  return Response::success((new ProfileService($this->factory->connection()))->update(ApiInput::body(['full_name']),$actor,$requestId));
 }
}
