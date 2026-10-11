<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Support\{ApiInput,CanonicalJson,AtomicWrite,DatabasePlatform};
use BizStock\Repositories\{ProductRepository,OperationRepository,AuditRepository,LookupRepository};
use BizStock\Http\{Authorization,HttpException};

final readonly class ProductService
{
    public function __construct(private \PDO $pdo){}
    public static function fields(): array{return ['sku','product_name','category_id','supplier_id','price','minimum_threshold','location','description'];}
    public static function project(array $row,string $role): array
    {
        $row['is_active']=(bool)$row['is_active'];$row['stock_status']=$row['quantity']===0?'out_of_stock':($row['quantity']<=$row['minimum_threshold']?'low_stock':'in_stock');
        foreach(['created_at','updated_at'] as $key){$row[$key]=(new \DateTimeImmutable($row[$key],new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');}return Authorization::product($row,$role);
    }
    public function listing(array $query,string $role,?int $exportLimit=null): array
    { $r=(new ProductRepository($this->pdo))->listing($query,$role,$exportLimit);$r['data']=array_map(fn($row)=>self::project($row,$role),$r['data']);return $r; }
    public function detail(int $id,string $role): array
    { $row=(new ProductRepository($this->pdo))->detail($id);if(!$row){throw new HttpException(404,'NOT_FOUND','Product not found.');}return self::project($row,$role); }
    private function validate(array $body,bool $create): array
    {
        if(array_diff(array_keys($body),[...self::fields(),...($create?['opening_quantity']:['expected_version'])])){throw new HttpException(422,'VALIDATION_ERROR','Unknown product field; quantity cannot be edited.');}
        $data=[];
        foreach(['sku'=>64,'product_name'=>150,'location'=>100,'description'=>2000] as $field=>$max){
            if(array_key_exists($field,$body)||($create&&in_array($field,['sku','product_name'],true))){$value=$body[$field]??null;$required=in_array($field,['sku','product_name'],true);$data[$field]=$value===null&&!$required?null:ApiInput::text($value,$field,$max,$required);if(!$required&&$data[$field]===''){$data[$field]=null;}}
        }
        if(isset($data['sku'])){$data['sku']=strtoupper($data['sku']);if(!preg_match('/^[A-Z0-9._-]{1,64}$/D',$data['sku'])){throw new HttpException(422,'VALIDATION_ERROR','Use an ASCII SKU with letters, digits, dot, underscore or hyphen.');}}
        foreach(['category_id','supplier_id','minimum_threshold'] as $field){if($create||array_key_exists($field,$body)){$data[$field]=ApiInput::integer($body[$field]??null,$field,$field==='minimum_threshold'?0:1);}}
        if($create||array_key_exists('price',$body)){$data['price']=ApiInput::decimal($body['price']??null,'price');}
        if($create){$data+=['location'=>null,'description'=>null];$data['opening_quantity']=ApiInput::integer($body['opening_quantity']??0,'opening_quantity');}
        else{if(!$data){throw new HttpException(422,'VALIDATION_ERROR','Provide product metadata to update.');}$data['expected_version']=ApiInput::integer($body['expected_version']??null,'expected_version',1);}
        return $data;
    }
    private function references(array $data): void
    { foreach(['category_id'=>'categories','supplier_id'=>'suppliers'] as $key=>$resource){if(isset($data[$key])&&!(new LookupRepository($this->pdo,$resource))->find($data[$key],true)){throw new HttpException(422,'INVALID_REFERENCE','Choose an existing category and supplier.');}} }
    private function map(callable $work): array
    {try{return $work();}catch(\PDOException $e){$code=DatabasePlatform::failureCode($e);if($code==='duplicate'){throw new HttpException(409,'DUPLICATE_SKU','A product with this SKU already exists.');}if($code==='reference'){throw new HttpException(422,'INVALID_REFERENCE','Choose an existing category and supplier.');}throw $e;}}
    public function create(array $body,array $actor,string $key,string $requestId): array
    {
        $data=$this->validate($body,true);$key=ApiInput::operationKey($key);$hash=hash('sha256',CanonicalJson::encode($data));
        return $this->map(fn()=>AtomicWrite::run($this->pdo,$actor,['Admin'],function($current)use($data,$key,$hash,$requestId){
            $ops=new OperationRepository($this->pdo);$replay=$ops->reserve((int)$current['user_id'],'product.create',$key,$hash);if($replay!==null){return $replay;}
            $this->references($data);$repo=new ProductRepository($this->pdo);$id=$repo->create($data);$movement=null;
            if($data['opening_quantity']>0){$opening=(new StockMovementService($this->pdo))->appendInTransaction(['product_id'=>$id,'type'=>'IN','quantity'=>$data['opening_quantity'],'notes'=>'Opening stock'],$current,$key,$hash);$movement=$opening['transaction_id'];}
            $result=$this->detail($id,'Admin');(new AuditRepository($this->pdo))->record((int)$current['user_id'],'product.created','product',$id,null,$result,$requestId);
            return $ops->complete((int)$current['user_id'],$key,$id,$movement,$result);
        }));
    }
    public function mutate(int $id,string $method,array $body,array $actor,string $requestId): array
    {
        if($method==='DELETE') {if(array_diff(array_keys($body),['expected_version'])){throw new HttpException(422,'VALIDATION_ERROR','Unknown archive field.');}$data=['expected_version'=>ApiInput::integer($body['expected_version']??null,'expected_version',1)];}
        else{$data=$this->validate($body,false);}
        return $this->map(fn()=>AtomicWrite::run($this->pdo,$actor,['Admin'],function($current)use($id,$method,$data,$requestId){
            $repo=new ProductRepository($this->pdo);$row=$repo->detail($id,true);if(!$row){throw new HttpException(404,'NOT_FOUND','Product not found.');}
            if($row['version']!==$data['expected_version']){throw new HttpException(409,'STALE_EDIT','The product changed. Reload it before editing.');}
            if($row['version']>=2147483647){throw new HttpException(409,'VERSION_OVERFLOW','Version limit exceeded.');}
            $before=self::project($row,'Admin');$changes=$data;unset($changes['expected_version']);
            if($method==='DELETE'){if(!$row['is_active']||$row['quantity']!==0){throw new HttpException(409,'ARCHIVE_CONFLICT','Only active products with zero stock can be archived.');}$changes=['is_active'=>0];}
            else {if(isset($changes['sku'])&&$changes['sku']!==$row['sku']&&$repo->hasMovements($id)){throw new HttpException(409,'SKU_IMMUTABLE','SKU cannot change after a movement.');}$this->references($changes);}
            $repo->update($id,$changes);$after=$this->detail($id,'Admin');(new AuditRepository($this->pdo))->record((int)$current['user_id'],$method==='DELETE'?'product.archived':'product.updated','product',$id,$before,$after,$requestId);return $after;
        }));
    }
}
