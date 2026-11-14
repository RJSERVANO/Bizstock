<?php
declare(strict_types=1);
namespace BizStock\Repositories;
use BizStock\Support\ApiInput;
final readonly class HistoryActorRepository
{
 public function __construct(private \PDO $pdo){}
 public function listing(array $query):array{
  $page=ApiInput::page($query,['user_id'=>'u.user_id','full_name'=>'u.full_name'],'full_name');$pattern='%'.strtr(ApiInput::text($query['q']??'','q',100,false),['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
  $where=" WHERE u.full_name LIKE ? ESCAPE '!' AND EXISTS (SELECT 1 FROM transactions t WHERE t.user_id=u.user_id)";
  $q=$this->pdo->prepare('SELECT COUNT(*) FROM users u'.$where);$q->execute([$pattern]);$total=(int)$q->fetchColumn();
  $q=$this->pdo->prepare('SELECT u.user_id,u.full_name FROM users u'.$where.' ORDER BY '.$page['order_by'].' '.$page['direction'].',u.user_id ASC LIMIT ? OFFSET ?');$q->bindValue(1,$pattern);$q->bindValue(2,$page['per_page'],\PDO::PARAM_INT);$q->bindValue(3,$page['offset'],\PDO::PARAM_INT);$q->execute();return ['data'=>$q->fetchAll(),'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$total,'total_pages'=>(int)ceil($total/$page['per_page'])]];
 }
}
