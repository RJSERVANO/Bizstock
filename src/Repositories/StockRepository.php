<?php
declare(strict_types=1);
namespace BizStock\Repositories;

final readonly class StockRepository
{
    public function __construct(private \PDO $pdo){}
    public function product(int $id): ?array
    { $q=$this->pdo->prepare('SELECT * FROM products WHERE product_id=? FOR UPDATE');$q->execute([$id]);return $q->fetch()?:null; }
    public function original(int $id): ?array
    { $q=$this->pdo->prepare('SELECT * FROM transactions WHERE transaction_id=?');$q->execute([$id]);return $q->fetch()?:null; }
    public function reversed(int $id): bool
    { $q=$this->pdo->prepare('SELECT transaction_id FROM transactions WHERE correction_of_transaction_id=? FOR UPDATE');$q->execute([$id]);return (bool)$q->fetch(); }
    public function append(array $product,int $actor,string $type,int $quantity,string $price,string $key,string $hash,?string $notes,?int $original): array
    {
        $q=$this->pdo->prepare('INSERT INTO transactions(product_id,user_id,type,quantity,unit_price,request_key,payload_hash,notes,correction_of_transaction_id) VALUES (?,?,?,?,?,?,?,?,?)');
        $q->execute([$product['product_id'],$actor,$type,$quantity,$price,$key,$hash,$notes,$original]);$id=(int)$this->pdo->lastInsertId();
        $balance=(int)$product['quantity']+($type==='IN'?$quantity:-$quantity);
        $q=$this->pdo->prepare('UPDATE products SET quantity=?,version=version+1,updated_at=UTC_TIMESTAMP(6) WHERE product_id=?');$q->execute([$balance,$product['product_id']]);
        $row=$this->original($id);unset($row['request_key'],$row['payload_hash']);
        $row['transaction_date']=(new \DateTimeImmutable($row['transaction_date'],new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z');
        $row['stock']=['product_id'=>(int)$product['product_id'],'quantity'=>$balance,'version'=>(int)$product['version']+1];return $row;
    }
}
