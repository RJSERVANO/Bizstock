<?php
declare(strict_types=1);
namespace BizStock\Http\Controllers;
use BizStock\Support\{PdoFactory,SessionManager,ApiInput};
use BizStock\Services\{AuthService,StockMovementService};
use BizStock\Repositories\HistoryRepository;
use BizStock\Http\{Authorization,Response,HttpException};

final readonly class StockController
{
    public function __construct(private PdoFactory $factory,private AuthService $auth,private SessionManager $sessions){}
    public function handle(string $method,?int $id,string $uri): Response
    {
        $actor=$this->auth->current();Authorization::requireRole($actor,['Admin','Staff']);
        $query=ApiInput::query(explode('?',$uri,2)[1]??'',$method==='GET'&&$id===null?['product_id','category_id','user_id','type','date_from','date_to','page','per_page','sort','direction']:[]);
        if($method==='POST') {
            $this->sessions->verifyCsrf();$body=ApiInput::body(['product_id','type','quantity','notes','correction_of_transaction_id']);
            if(array_key_exists('correction_of_transaction_id',$body)){Authorization::requireRole($actor,['Admin']);}
            return Response::success((new StockMovementService($this->factory->connection()))->record($body,$actor,ApiInput::operationKey($_SERVER['HTTP_IDEMPOTENCY_KEY']??null)),[],201);
        }
        $history=new HistoryRepository($this->factory->connection());
        if($id!==null){$row=$history->detail($id);if(!$row){throw new HttpException(404,'NOT_FOUND','Movement not found.');}return Response::success($row);}
        $result=$history->listing($query);return Response::success($result['data'],$result['meta']);
    }
}
