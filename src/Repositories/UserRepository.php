<?php
declare(strict_types=1);
namespace BizStock\Repositories;
use BizStock\Support\ApiInput;
use BizStock\Http\Authorization;
final readonly class UserRepository
{
 public function __construct(private \PDO $pdo){}
 public function listing(array $query):array{
  $where=[];$values=[];
  if(isset($query['q'])){$where[]="(username LIKE ? ESCAPE '!' OR full_name LIKE ? ESCAPE '!')";$pattern='%'.strtr(ApiInput::text($query['q'],'q',100,false),['!'=>'!!','%'=>'!%','_'=>'!_']).'%';$values=[$pattern,$pattern];}
  if(isset($query['is_active'])){$where[]='is_active=?';$values[]=ApiInput::boolean($query['is_active'],'is_active')?1:0;}
  $suffix=$where?' WHERE '.implode(' AND ',$where):'';$page=ApiInput::page($query,['user_id'=>'user_id','username'=>'username','full_name'=>'full_name','role'=>'role'],'user_id');
  $q=$this->pdo->prepare('SELECT COUNT(*) FROM users'.$suffix);$q->execute($values);$total=(int)$q->fetchColumn();
  $q=$this->pdo->prepare('SELECT user_id,full_name,username,role,is_active,must_change_password,created_at,updated_at,profile_image FROM users'.$suffix.' ORDER BY '.$page['order_by'].' '.$page['direction'].',user_id ASC LIMIT ? OFFSET ?');foreach($values as $i=>$v)$q->bindValue($i+1,$v);$q->bindValue(count($values)+1,$page['per_page'],\PDO::PARAM_INT);$q->bindValue(count($values)+2,$page['offset'],\PDO::PARAM_INT);$q->execute();
  return ['data'=>array_map(Authorization::user(...),$q->fetchAll()),'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$total,'total_pages'=>(int)ceil($total/$page['per_page'])]];
 }
 public function create(array $d):int{$q=$this->pdo->prepare('INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,?,1)');$q->execute([$d['full_name'],$d['username'],$d['password_hash'],$d['role']]);return (int)$this->pdo->lastInsertId();}
 public function update(int $id,array $d):void{
  if(array_diff(array_keys($d),['full_name','username','role','is_active','password_hash','must_change_password']))throw new \LogicException('Untrusted user columns.');
  $q=$this->pdo->prepare('UPDATE users SET '.implode(',',array_map(fn($k)=>$k.'=?',array_keys($d))).',session_version=session_version+1,updated_at=UTC_TIMESTAMP(6) WHERE user_id=?');$q->execute([...array_values($d),$id]);
 }
 public function activeAdmins():int{return (int)$this->pdo->query("SELECT COUNT(*) FROM users WHERE role='Admin' AND is_active=1")->fetchColumn();}
}
