<?php
declare(strict_types=1);
namespace BizStock\Repositories;
use BizStock\Support\ApiInput;
use BizStock\Http\HttpException;

final readonly class ProductRepository
{
    public function __construct(private \PDO $pdo){}
    private const SELECT='SELECT p.*,c.category_name,s.supplier_name FROM products p JOIN categories c ON c.category_id=p.category_id JOIN suppliers s ON s.supplier_id=p.supplier_id';
    public function detail(int $id,bool $lock=false): ?array
    { $q=$this->pdo->prepare(self::SELECT.' WHERE p.product_id=?'.($lock?' FOR UPDATE':''));$q->execute([$id]);return $q->fetch()?:null; }
    public static function filters(array $query,string $role): array
    {
        if($role==='Viewer'&&(isset($query['supplier_id'])||in_array($query['sort']??'',['price','supplier_id','supplier_name'],true))){throw new HttpException(403,'FORBIDDEN','Access denied.');}
        $where=[];$values=[];
        if(isset($query['q'])){$text=ApiInput::text($query['q'],'q',100,false);$pattern='%'.strtr($text,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';$where[]="(p.product_name LIKE ? ESCAPE '!' OR p.sku LIKE ? ESCAPE '!')";$values[]=$pattern;$values[]=$pattern;}
        foreach(['category_id','supplier_id','product_id'] as $field){if(isset($query[$field])){$where[]='p.'.$field.'=?';$values[]=ApiInput::integer($query[$field],$field,1);}}
        $active=ApiInput::choice($query['active']??'active','active',['active','archived','all']);if($active!=='all'){$where[]='p.is_active=?';$values[]=$active==='active'?1:0;}
        if(isset($query['stock_status'])){$status=ApiInput::choice($query['stock_status'],'stock_status',['in_stock','low_stock','out_of_stock']);$where[]=match($status){'out_of_stock'=>'p.quantity=0','low_stock'=>'p.quantity>0 AND p.quantity<=p.minimum_threshold',default=>'p.quantity>p.minimum_threshold'};}
        if(isset($query['low_stock'])){$low=ApiInput::boolean($query['low_stock'],'low_stock');$where[]=$low?'p.quantity<=p.minimum_threshold':'p.quantity>p.minimum_threshold';}
        return [' WHERE '.implode(' AND ',$where?:['1=1']),$values];
    }
    public function listing(array $query,string $role,?int $exportLimit=null): array
    {
        [$where,$values]=self::filters($query,$role);$map=['product_id'=>'p.product_id','product_name'=>'p.product_name','sku'=>'p.sku','quantity'=>'p.quantity'];if($role!=='Viewer'){$map['price']='p.price';}
        $page=ApiInput::page($query,$map,'product_id');$q=$this->pdo->prepare('SELECT COUNT(*) FROM products p'.$where);$q->execute($values);$total=(int)$q->fetchColumn();
        if($exportLimit!==null&&$total>$exportLimit){throw new HttpException(422,'EXPORT_TOO_LARGE','Narrow the filters to at most 10,000 rows.');}
        $q=$this->pdo->prepare(self::SELECT.$where.' ORDER BY '.$page['order_by'].' '.$page['direction'].',p.product_id ASC LIMIT ? OFFSET ?');foreach($values as $i=>$value){$q->bindValue($i+1,$value);}$q->bindValue(count($values)+1,$exportLimit??$page['per_page'],\PDO::PARAM_INT);$q->bindValue(count($values)+2,$exportLimit!==null?0:$page['offset'],\PDO::PARAM_INT);$q->execute();
        return ['data'=>$q->fetchAll(),'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$total,'total_pages'=>(int)ceil($total/$page['per_page'])]];
    }
    public function create(array $data): int
    { $columns=['sku','product_name','category_id','supplier_id','price','minimum_threshold','location','description'];$q=$this->pdo->prepare('INSERT INTO products('.implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');$q->execute(array_map(fn($key)=>$data[$key],$columns));return (int)$this->pdo->lastInsertId(); }
    public function update(int $id,array $data): void
    {
        if(array_diff(array_keys($data),['sku','product_name','category_id','supplier_id','price','minimum_threshold','location','description','is_active'])){throw new \LogicException('Untrusted metadata columns.');}
        $q=$this->pdo->prepare('UPDATE products SET '.implode(',',array_map(fn($key)=>$key.'=?',array_keys($data))).',version=version+1,updated_at=UTC_TIMESTAMP(6) WHERE product_id=?');$q->execute([...array_values($data),$id]);
    }
    public function hasMovements(int $id): bool
    { $q=$this->pdo->prepare('SELECT transaction_id FROM transactions WHERE product_id=? LIMIT 1');$q->execute([$id]);return (bool)$q->fetch(); }
}
