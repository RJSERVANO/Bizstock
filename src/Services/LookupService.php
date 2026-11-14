<?php
declare(strict_types=1);
namespace BizStock\Services;

use BizStock\Http\{Authorization,HttpException};
use BizStock\Repositories\{LookupRepository,AuthRepository,AuditRepository};
use BizStock\Support\{ApiInput,PdoFactory,DatabasePlatform};

final readonly class LookupService
{
    public function __construct(private PdoFactory $factory) {}
    public static function fields(string $resource): array
    { return $resource==='categories'?['category_name']:['supplier_name','contact_email']; }
    public static function project(string $resource, array $row): array
    {
        if($resource==='categories'){return Authorization::category($row)+array_intersect_key($row,array_flip(['active_product_count','archived_product_count','edit_version']));}
        $safe=array_intersect_key($row,array_flip(['supplier_id','supplier_name','contact_email','created_at','active_product_count','archived_product_count','edit_version']));
        $safe['created_at']=(new \DateTimeImmutable($safe['created_at'],new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'); return $safe;
    }
    public function listing(string $resource, array $query): array
    {
        $id=$resource==='categories'?'category_id':'supplier_id'; $name=self::fields($resource)[0];
        $page=ApiInput::page($query,[$id=>$id,$name=>$name],$id);
        $search=isset($query['q'])?ApiInput::text($query['q'],'q',100,false):'';
        $result=(new LookupRepository($this->factory->connection(),$resource))->listing($search,$page,isset($query['include_products'])?ApiInput::boolean($query['include_products'],'include_products'):false);
        if(!($query['include_edit_version']??false))foreach($result['rows'] as &$row)unset($row['edit_version']);unset($row);
        return ['data'=>array_map(static fn(array $row)=>self::project($resource,$row),$result['rows']),'meta'=>$result['meta']];
    }
    private function validate(string $resource, array $body, bool $create): array
    {
        $name=self::fields($resource)[0]; $data=[];
        if($create || array_key_exists($name,$body)) { $data[$name]=ApiInput::text($body[$name]??null,$name,$resource==='categories'?100:150); }
        if($resource==='suppliers' && ($create || array_key_exists('contact_email',$body))) {
            $email=$body['contact_email']??null;
            if($email!==null) {
                $email=ApiInput::text($email,'contact_email',100,false);
                if($email===''){$email=null;}
                elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) { throw new HttpException(422,'VALIDATION_ERROR','Request validation failed.',['contact_email'=>['Enter a valid email address.']]); }
            }
            $data['contact_email']=$email;
        }
        if(!$data) { throw new HttpException(422,'VALIDATION_ERROR','Provide at least one lookup field.'); } return $data;
    }
    public function mutate(string $resource, string $method, ?int $id, array $body, array $actor, string $requestId): array
    {
        $expected=isset($body['expected_edit_version'])?ApiInput::integer($body['expected_edit_version'],'expected_edit_version',1):null;unset($body['expected_edit_version']);
        $data=$method==='DELETE'?[]:$this->validate($resource,$body,$method==='POST');
        $pdo=$this->factory->connection(); $repository=new LookupRepository($pdo,$resource); $pdo->beginTransaction();
        try {
            $current=(new AuthRepository($pdo))->byId((int)$actor['user_id'],true);
            if(!$current || !$current['is_active'] || (int)$current['session_version']!==(int)$actor['session_version']) { throw new HttpException(401,'UNAUTHENTICATED','Sign in to continue.'); }
            Authorization::requireRole($current,['Admin']);
            if($current['must_change_password']) {throw new HttpException(403,'PASSWORD_CHANGE_REQUIRED','Change your password to continue.');}
            $before=$id===null?null:$repository->find($id,true);
            if($id!==null && !$before){throw new HttpException(404,'NOT_FOUND','Record not found.');}
            if($before&&$expected!==null&&$expected!==(int)$before['edit_version'])throw new HttpException(409,'STALE_EDIT','This record changed. Your entries are retained; reload and review before saving.');if($before&&$before['edit_version']>=2147483647)throw new HttpException(409,'VERSION_OVERFLOW','Edit revision limit reached.');
            if($method==='DELETE') {
                if($repository->referenced($id)){throw new HttpException(409,'REFERENCED_RECORD','Reassign every product, including archived products, before deleting this record.');}
                $repository->delete($id); $after=null;
            } else { $after=$method==='POST'?$repository->create($data):$repository->update($id,$data); }
            $entity=$resource==='categories'?'category':'supplier'; $action=match($method){'POST'=>'created','PATCH'=>'updated','DELETE'=>'deleted'};
            $entityId=$id??(int)$after[$repository->id];
            (new AuditRepository($pdo))->record((int)$current['user_id'],$entity.'.'.$action,$entity,$entityId,
                $before===null?null:self::project($resource,$before),$after===null?null:self::project($resource,$after),$requestId);
            $pdo->commit(); return $after===null?['deleted'=>true]:self::project($resource,$after);
        } catch(\Throwable $error) {
            if($pdo->inTransaction()){$pdo->rollBack();}
            if($error instanceof \PDOException) {
                $code=DatabasePlatform::failureCode($error);
                if($code==='duplicate' && $resource==='categories'){throw new HttpException(409,'DUPLICATE_CATEGORY','A category with this name already exists.');}
                if($code==='reference' && $method==='DELETE'){throw new HttpException(409,'REFERENCED_RECORD','Reassign every product, including archived products, before deleting this record.');}
            }
            throw $error;
        }
    }
}
