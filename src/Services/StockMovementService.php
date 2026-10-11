<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Http\{Authorization,HttpException};
use BizStock\Support\{ApiInput,AtomicWrite,CanonicalJson};
use BizStock\Repositories\{StockRepository,OperationRepository};

final readonly class StockMovementService
{
    public function __construct(private \PDO $pdo){}
    public static function validate(array $body): array
    {
        if(array_key_exists('correction_of_transaction_id',$body)) {
            if(array_diff(array_keys($body),['correction_of_transaction_id','notes'])){throw new HttpException(422,'VALIDATION_ERROR','A reversal derives product, type, quantity and price from its original.');}
            return ['correction_of_transaction_id'=>ApiInput::integer($body['correction_of_transaction_id'],'correction_of_transaction_id',1),'notes'=>ApiInput::text($body['notes']??null,'notes',2000)];
        }
        if(array_diff(array_keys($body),['product_id','type','quantity','notes'])){throw new HttpException(422,'VALIDATION_ERROR','Unknown movement field.');}
        return ['product_id'=>ApiInput::integer($body['product_id']??null,'product_id',1),'type'=>ApiInput::choice($body['type']??null,'type',['IN','OUT']),
            'quantity'=>ApiInput::integer($body['quantity']??null,'quantity',1),'notes'=>($body['notes']??null)===null?null:ApiInput::text($body['notes'],'notes',2000,false)];
    }
    public function record(array $body,array $actor,string $key): array
    {
        $data=self::validate($body);$key=ApiInput::operationKey($key);
        $roles=isset($data['correction_of_transaction_id'])?['Admin']:['Admin','Staff'];
        return AtomicWrite::run($this->pdo,$actor,$roles,fn(array $current)=>$this->recordInTransaction($data,$current,$key));
    }
    // Trusted callers own the transaction and locked/current authorized actor; never commits nested work.
    public function recordInTransaction(array $data,array $actor,string $key): array
    {
        if(!$this->pdo->inTransaction()){throw new \LogicException('Movement requires caller transaction.');}
        $data=self::validate($data);$key=ApiInput::operationKey($key);
        $reversal=isset($data['correction_of_transaction_id']);Authorization::requireRole($actor,$reversal?['Admin']:['Admin','Staff']);
        if(!$actor['is_active'] || $actor['must_change_password']){throw new HttpException(403,'FORBIDDEN','Access denied.');}
        $hash=hash('sha256',CanonicalJson::encode($data));$ops=new OperationRepository($this->pdo);
        $replay=$ops->reserve((int)$actor['user_id'],'movement',$key,$hash);if($replay!==null){return $replay;}
        $result=$this->appendInTransaction($data,$actor,$key,$hash);return $ops->complete((int)$actor['user_id'],$key,$result['product_id'],$result['transaction_id'],$result);
    }
    public function appendInTransaction(array $data,array $actor,string $key,string $hash): array
    {
        if(!$this->pdo->inTransaction()){throw new \LogicException('Movement requires caller transaction.');}
        $data=self::validate($data);$key=ApiInput::operationKey($key);
        Authorization::requireRole($actor,isset($data['correction_of_transaction_id'])?['Admin']:['Admin','Staff']);
        if(!$actor['is_active']||$actor['must_change_password']){throw new HttpException(403,'FORBIDDEN','Access denied.');}
        $repository=new StockRepository($this->pdo);$original=null;
        if(isset($data['correction_of_transaction_id'])) {
            Authorization::requireRole($actor,['Admin']);$original=$repository->original($data['correction_of_transaction_id']);
            if(!$original){throw new HttpException(404,'NOT_FOUND','Original movement not found.');}
            if($original['correction_of_transaction_id']!==null){throw new HttpException(409,'REVERSAL_CONFLICT','A reversal cannot itself be reversed.');}
            $data=['product_id'=>(int)$original['product_id'],'type'=>$original['type']==='IN'?'OUT':'IN','quantity'=>(int)$original['quantity'],'notes'=>$data['notes']];
        }
        $product=$repository->product($data['product_id']);
        if(!$product){throw new HttpException(404,'NOT_FOUND','Product not found.');}
        if(!$product['is_active']){throw new HttpException(409,'PRODUCT_ARCHIVED','Archived products cannot receive movements.');}
        if($original && $repository->reversed((int)$original['transaction_id'])){throw new HttpException(409,'REVERSAL_CONFLICT','The original movement already has a reversal.');}
        $quantity=$data['quantity'];$balance=(int)$product['quantity']+($data['type']==='IN'?$quantity:-$quantity);
        if($balance<0){throw new HttpException(409,'INSUFFICIENT_STOCK','Available stock is insufficient.');}
        if($balance>2147483647 || (int)$product['version']>=2147483647){throw new HttpException(409,'STOCK_OVERFLOW','Stock or version limit exceeded.');}
        return $repository->append($product,(int)$actor['user_id'],$data['type'],$quantity,$original['unit_price']??$product['price'],$key,$hash,$data['notes'],$original?(int)$original['transaction_id']:null);
    }
}
