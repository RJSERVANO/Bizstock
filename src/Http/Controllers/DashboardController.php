<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Support\{PdoFactory,ApiInput};use BizStock\Services\{AuthService,DashboardService};use BizStock\Http\Response;
final readonly class DashboardController
{
 public function __construct(private PdoFactory $factory,private AuthService $auth){}
 public function handle(string $uri): Response
 { $actor=$this->auth->current();$query=ApiInput::query(explode('?',$uri,2)[1]??'',['period']);if($actor['role']==='Viewer'&&isset($query['period']))throw new \BizStock\Http\HttpException(403,'FORBIDDEN','Movement activity is restricted.');$period=\BizStock\Support\ApiInput::choice($query['period']??'7','period',['7','30']);return Response::success((new DashboardService($this->factory->connection()))->read($actor['role'],(int)$period)); }
}
