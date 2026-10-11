<?php
declare(strict_types=1);namespace BizStock\Repositories;
use BizStock\Support\ApiInput;
final readonly class AuditLogRepository{
 public function __construct(private \PDO $pdo){}
 public function listing(array $query):array{
  $where=[];$values=[];foreach(['entity_type','entity_id','actor_id'] as $key)if(isset($query[$key])){$where[]=$key.'=?';$values[]=$key==='entity_type'?ApiInput::text($query[$key],$key,40):ApiInput::integer($query[$key],$key,1);}
  foreach(ApiInput::dateRange($query) as $key=>$date){$time=new \DateTimeImmutable($date,new \DateTimeZone('Asia/Manila'));if($key==='date_to')$time=$time->modify('+1 day');$where[]='occurred_at '.($key==='date_from'?'>=':'<').' ?';$values[]=$time->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
  $suffix=$where?' WHERE '.implode(' AND ',$where):'';$page=ApiInput::page($query,['event_id'=>'event_id','occurred_at'=>'occurred_at'],'occurred_at');$q=$this->pdo->prepare('SELECT COUNT(*) FROM audit_events'.$suffix);$q->execute($values);$total=(int)$q->fetchColumn();
  $q=$this->pdo->prepare('SELECT event_id,actor_id,action,entity_type,entity_id,before_json,after_json,occurred_at,request_id FROM audit_events'.$suffix.' ORDER BY '.$page['order_by'].' '.$page['direction'].',event_id '.$page['direction'].' LIMIT ? OFFSET ?');foreach($values as $i=>$v)$q->bindValue($i+1,$v);$q->bindValue(count($values)+1,$page['per_page'],\PDO::PARAM_INT);$q->bindValue(count($values)+2,$page['offset'],\PDO::PARAM_INT);$q->execute();$rows=$q->fetchAll();foreach($rows as &$r){$r['event_id']=(string)$r['event_id'];$r['entity_id']=$r['entity_id']===null?null:(string)$r['entity_id'];foreach(['before','after'] as $key){$r[$key]=$r[$key.'_json']===null?null:json_decode($r[$key.'_json'],true,32,JSON_THROW_ON_ERROR);unset($r[$key.'_json']);}$r['occurred_at']=(new \DateTimeImmutable($r['occurred_at'],new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');}
  return ['data'=>$rows,'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$total,'total_pages'=>(int)ceil($total/$page['per_page'])]];
 }
}
