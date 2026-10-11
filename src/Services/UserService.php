<?php
declare(strict_types=1);
namespace BizStock\Services;
use BizStock\Support\{ApiInput,AtomicWrite,PasswordPolicy,DatabasePlatform};
use BizStock\Repositories\{AuthRepository,UserRepository,AuditRepository};
use BizStock\Http\{Authorization,HttpException};
final readonly class UserService
{
 public function __construct(private \PDO $pdo){}
 private static function hash(mixed $password):string{if(!is_string($password))throw new HttpException(422,'VALIDATION_ERROR','Supply a valid password.');try{return PasswordPolicy::hash($password);}catch(\InvalidArgumentException){throw new HttpException(422,'VALIDATION_ERROR','Password must contain 12–72 UTF-8 bytes without NUL.');}}
 private static function fields(array $body,bool $create):array{
  if(array_diff(array_keys($body),$create?['username','full_name','role','password']:['username','full_name','role','is_active']))throw new HttpException(422,'VALIDATION_ERROR','Unknown account field.');$d=[];
  foreach(['username'=>50,'full_name'=>100] as $k=>$max)if($create||array_key_exists($k,$body))$d[$k]=ApiInput::text($body[$k]??null,$k,$max);
  if(isset($d['username'])){$d['username']=strtolower($d['username']);if(!preg_match('/^[a-z0-9._-]{1,50}$/D',$d['username']))throw new HttpException(422,'VALIDATION_ERROR','Invalid username.');}
  if($create||array_key_exists('role',$body))$d['role']=ApiInput::choice($body['role']??null,'role',['Admin','Staff','Viewer']);
  if(array_key_exists('is_active',$body)){$d['is_active']=ApiInput::boolean($body['is_active'],'is_active')?1:0;}
  if($create)$d['password_hash']=self::hash($body['password']??null);if(!$d)throw new HttpException(422,'VALIDATION_ERROR','Provide account changes.');return $d;
 }
 public function write(string $method,?int $id,array $body,array $actor,string $requestId,bool $reset=false):array{
  if($reset){if(array_keys($body)!==['new_password'])throw new HttpException(422,'VALIDATION_ERROR','Supply only new_password.');$d=['password_hash'=>self::hash($body['new_password']),'must_change_password'=>1];}
  elseif($method==='DELETE'){if($body)throw new HttpException(422,'VALIDATION_ERROR','Deactivate accepts an empty object.');$d=['is_active'=>0];}
  else $d=self::fields($body,$method==='POST');
  try{return AtomicWrite::run($this->pdo,$actor,['Admin'],function($current)use($method,$id,$d,$requestId,$reset){
   $repo=new UserRepository($this->pdo);$auth=new AuthRepository($this->pdo);$before=null;
   if($method==='POST'&&!$reset){$id=$repo->create($d);$action='user.created';}
   else{$row=$auth->byId($id,true);if(!$row)throw new HttpException(404,'NOT_FOUND','Account not found.');if($row['session_version']>=2147483647)throw new HttpException(409,'VERSION_OVERFLOW','Account version limit exceeded.');
    if($row['is_active']&&$row['role']==='Admin'&&(($d['role']??'Admin')!=='Admin'||($d['is_active']??1)===0)&&$repo->activeAdmins()<=1)throw new HttpException(409,'LAST_ADMIN','Keep at least one active Admin.');
    $before=Authorization::user($row);$repo->update($id,$d);$action=$reset?'user.password_reset':($method==='DELETE'?'user.deactivated':'user.updated');
   }
   $after=Authorization::user($auth->byId($id));(new AuditRepository($this->pdo))->record((int)$current['user_id'],$action,'user',$id,$before,$after,$requestId);return $after;
  },true);}catch(\PDOException $e){if(DatabasePlatform::failureCode($e)==='duplicate')throw new HttpException(409,'DUPLICATE_USERNAME','Username already exists.');throw $e;}
 }
 public function recover(int $id,string $password,string $reason):array{
  $hash=self::hash($password);$reason=ApiInput::text($reason,'reason',500);$this->pdo->beginTransaction();try{
   if((int)$this->pdo->query('SELECT lock_id FROM security_locks WHERE lock_id=1 FOR UPDATE')->fetchColumn()!==1)throw new \RuntimeException('Security sentinel missing.');
   $auth=new AuthRepository($this->pdo);$row=$auth->byId($id,true);if(!$row)throw new HttpException(404,'NOT_FOUND','Existing recovery account not found.');if($row['session_version']>=2147483647)throw new HttpException(409,'VERSION_OVERFLOW','Account version limit exceeded.');
   (new UserRepository($this->pdo))->update($id,['role'=>'Admin','is_active'=>1,'password_hash'=>$hash,'must_change_password'=>1]);$after=Authorization::user($auth->byId($id));(new AuditRepository($this->pdo))->record($id,'admin.emergency_recovery','user',$id,Authorization::user($row),$after+['reason'=>$reason],'cli-'.bin2hex(random_bytes(16)));$this->pdo->commit();return $after;
  }catch(\Throwable $e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
 }
}
