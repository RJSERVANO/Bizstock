<?php
declare(strict_types=1);
namespace BizStock\Repositories;

final readonly class LookupRepository
{
    public string $id;
    public string $name;
    public function __construct(private \PDO $pdo, private string $resource)
    {
        if (!in_array($resource,['categories','suppliers'],true)) { throw new \InvalidArgumentException('Unknown lookup.'); }
        $this->id=$resource==='categories'?'category_id':'supplier_id';
        $this->name=$resource==='categories'?'category_name':'supplier_name';
    }
    public function find(int $id, bool $lock=false): ?array
    {
        $query=$this->pdo->prepare("SELECT * FROM {$this->resource} WHERE {$this->id}=?".($lock?' FOR UPDATE':''));
        $query->execute([$id]); return $query->fetch()?:null;
    }
    public function listing(string $search, array $page): array
    {
        $pattern='%'.strtr($search,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
        $where=" WHERE {$this->name} LIKE ? ESCAPE '!'";
        $count=$this->pdo->prepare("SELECT COUNT(*) FROM {$this->resource}{$where}"); $count->execute([$pattern]);
        $total=(int)$count->fetchColumn();
        // page/order identifiers originate exclusively from ApiInput's trusted server map.
        $sql="SELECT * FROM {$this->resource}{$where} ORDER BY {$page['order_by']} {$page['direction']}, {$this->id} ASC LIMIT ? OFFSET ?";
        $query=$this->pdo->prepare($sql); $query->bindValue(1,$pattern); $query->bindValue(2,$page['per_page'],\PDO::PARAM_INT); $query->bindValue(3,$page['offset'],\PDO::PARAM_INT); $query->execute();
        return ['rows'=>$query->fetchAll(),'meta'=>['page'=>$page['page'],'per_page'=>$page['per_page'],'total'=>$total,'total_pages'=>(int)ceil($total/$page['per_page'])]];
    }
    public function create(array $data): array
    {
        $columns=$this->resource==='categories'?[$this->name]:[$this->name,'contact_email'];
        $query=$this->pdo->prepare("INSERT INTO {$this->resource} (".implode(',',$columns).') VALUES ('.implode(',',array_fill(0,count($columns),'?')).')');
        $query->execute(array_map(static fn(string $key)=>$data[$key],$columns)); return $this->find((int)$this->pdo->lastInsertId());
    }
    public function update(int $id, array $data): array
    {
        $columns=array_keys($data); $allowed=[$this->name]; if($this->resource==='suppliers'){$allowed[]='contact_email';}
        if(array_diff($columns,$allowed) || !$columns){throw new \InvalidArgumentException('Invalid lookup fields.');}
        $query=$this->pdo->prepare("UPDATE {$this->resource} SET ".implode(',',array_map(static fn(string $key)=>$key.'=?',$columns))." WHERE {$this->id}=?");
        $query->execute([...array_values($data),$id]); return $this->find($id);
    }
    public function referenced(int $id): bool
    {
        $query=$this->pdo->prepare("SELECT product_id FROM products WHERE {$this->id}=? LIMIT 1"); $query->execute([$id]); return $query->fetch()!==false;
    }
    public function delete(int $id): void
    { $query=$this->pdo->prepare("DELETE FROM {$this->resource} WHERE {$this->id}=?"); $query->execute([$id]); }
}
