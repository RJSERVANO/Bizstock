<?php
declare(strict_types=1);
namespace BizStock\Support;
use PDO;

final class StockReconciliation
{
    public function __construct(private readonly PDO $pdo) {}
    public function inspect(): array
    {
        if ($this->pdo->inTransaction()) { throw new \RuntimeException('Reconciliation owns a read-only snapshot.'); }
        $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
        try {
            $rows=$this->pdo->query("SELECT p.product_id,p.sku,p.is_active,p.quantity,COALESCE(m.balance,0) AS movement_balance FROM products p LEFT JOIN (SELECT product_id,SUM(CASE WHEN type='IN' THEN CAST(quantity AS DECIMAL(30,0)) ELSE -CAST(quantity AS DECIMAL(30,0)) END) AS balance FROM transactions GROUP BY product_id) m ON m.product_id=p.product_id ORDER BY p.product_id")->fetchAll();
            $drift=array_values(array_filter($rows,static fn(array $row):bool=>(string)$row['quantity']!==(string)$row['movement_balance']));
            $this->pdo->commit(); return ['products_checked'=>count($rows),'discrepancies'=>$drift];
        } catch(\Throwable $error) { if($this->pdo->inTransaction()){$this->pdo->rollBack();} throw $error; }
    }
}
