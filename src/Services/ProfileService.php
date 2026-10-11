<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Support\{ApiInput,AtomicWrite};
use BizStock\Repositories\{AuthRepository,AuditRepository};
use BizStock\Http\Authorization;
final readonly class ProfileService
{
 public function __construct(private \PDO $pdo){}
 public function update(array $body,array $actor,string $requestId): array
 {
  if(array_diff(array_keys($body),['full_name']))throw new \BizStock\Http\HttpException(422,'VALIDATION_ERROR','Only your display name can be changed here.');
  $name=ApiInput::text($body['full_name']??null,'full_name',100);
  return AtomicWrite::run($this->pdo,$actor,['Admin','Staff','Viewer'],function(array $current)use($name,$requestId){
   $repository=new AuthRepository($this->pdo);$id=(int)$current['user_id'];
   if($current['full_name']!==$name){$repository->updateName($id,$name);(new AuditRepository($this->pdo))->record($id,'account.profile_updated','user',$id,['full_name'=>$current['full_name']],['full_name'=>$name],$requestId);}
   return Authorization::user($repository->byId($id));
  });
 }
}
