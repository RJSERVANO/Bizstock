<?php
declare(strict_types=1);
namespace BizStock\Repositories;
use BizStock\Support\ApiInput;

final readonly class HistoryRepository
{
    public function __construct(private \PDO $pdo){}
    public static function filters(array $query): array
    {
        $where=[];$values=[];
        foreach(['product_id','user_id','category_id'] as $field){if(isset($query[$field])){$where[]=($field==='category_id'?'p.':'t.').$field.'=?';$values[]=ApiInput::integer($query[$field],$field,1);}}
        if(isset($query['type'])){$where[]='t.type=?';$values[]=ApiInput::choice($query['type'],'type',['IN','OUT']);}
        $dates=ApiInput::dateRange($query);
        foreach($dates as $field=>$date){$time=new \DateTimeImmutable($date,new \DateTimeZone('Asia/Manila'));if($field==='date_to'){$time=$time->modify('+1 day');}$where[]='t.transaction_date '.($field==='date_from'?'>=':'<').' ?';$values[]=$time->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
        return [($where?' WHERE '.implode(' AND ',$where):''),$values];
    }
    private const SELECT="SELECT t.transaction_id,t.product_id,t.user_id,t.type,t.quantity,t.unit_price,t.transaction_date,t.correction_of_transaction_id,t.notes,p.sku,p.product_name,u.full_name AS actor_name,r.transaction_id AS reversed_by_transaction_id FROM transactions t JOIN products p ON p.product_id=t.product_id JOIN users u ON u.user_id=t.user_id LEFT JOIN transactions r ON r.correction_of_transaction_id=t.transaction_id";
    public static function project(array $row): array
    { $row['transaction_date']=(new \DateTimeImmutable($row['transaction_date'],new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');return $row; }
    public function detail(int $id): ?array
    { $q=$this->pdo->prepare(self::SELECT.' WHERE t.transaction_id=?');$q->execute([$id]);$row=$q->fetch();return $row?self::project($row):null; }
    public function listing(array $query,?int $exportLimit=null): array
    {
        [$where,$values]=self::filters($query);$page=ApiInput::page($query,['transaction_id'=>'t.transaction_id','transaction_date'=>'t.transaction_date','quantity'=>'t.quantity'],'transaction_date');
        $q=$this->pdo->prepare('SELECT COUNT(*) AS total,COALESCE(SUM(CAST(t.quantity AS DECIMAL(30,0))*t.unit_price),0) AS reference_value FROM transactions t JOIN products p ON p.product_id=t.product_id'.$where);$q->execute($values);$aggregate=$q->fetch();$total=(int)$aggregate['total'];
        if($exportLimit!==null && $total>$exportLimit){throw new \BizStock\Http\HttpException(422,'EXPORT_TOO_LARGE','Narrow the filters to at most 10,000 rows.');}
        $q=$this->pdo->prepare(self::SELECT.$where.' ORDER BY '.$page['order_by'].' '.$page['direction'].',t.transaction_id '.$page['direction'].' LIMIT ? OFFSET ?');
        foreach($values as $index=>$value){$q->bindValue($index+1,$value);} $q->bindValue(count($values)+1,$exportLimit??$page['per_page'],\PDO::PARAM_INT);$q->bindValue(count($values)+2,$exportLimit!==null?0:$page['offset'],\PDO::PARAM_INT);$q->execute();
        return ['data'=>array_map(self::project(...),$q->fetchAll()),'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$total,'total_pages'=>(int)ceil($total/$page['per_page'])],'reference_value'=>(string)$aggregate['reference_value']];
    }
}
