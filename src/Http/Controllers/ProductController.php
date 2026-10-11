<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Http\{Response,Authorization};
use BizStock\Support\{ApiInput,PdoFactory,SessionManager};
use BizStock\Services\{ProductService,AuthService};

final readonly class ProductController
{
    public function __construct(private PdoFactory $factory,private AuthService $auth,private SessionManager $sessions){}
    public function handle(string $method,?int $id,string $uri,string $requestId): Response
    {
        $actor=$this->auth->current();if($method!=='GET'){Authorization::requireRole($actor,['Admin']);$this->sessions->verifyCsrf();}
        $query=ApiInput::query(explode('?',$uri,2)[1]??'',$method==='GET'&&$id===null?['q','category_id','supplier_id','active','stock_status','low_stock','page','per_page','sort','direction']:[]);
        $service=new ProductService($this->factory->connection());
        if($method==='GET'){if($id!==null){return Response::success($service->detail($id,$actor['role']));}$r=$service->listing($query,$actor['role']);return Response::success($r['data'],$r['meta']);}
        $body=ApiInput::body($method==='DELETE'?['expected_version']:[...ProductService::fields(),...($method==='POST'?['opening_quantity']:['expected_version'])]);
        return Response::success($method==='POST'?$service->create($body,$actor,ApiInput::operationKey($_SERVER['HTTP_IDEMPOTENCY_KEY']??null),$requestId):$service->mutate($id,$method,$body,$actor,$requestId),[],$method==='POST'?201:200);
    }
}
