<?php
declare(strict_types=1);
namespace BizStock\Repositories;
final readonly class DashboardRepository
{
 public function __construct(private \PDO $pdo){}
 public function counts(): array
 {
  $r=$this->pdo->query('SELECT COUNT(*) AS active_products,CAST(COALESCE(SUM(CAST(quantity AS DECIMAL(30,0))),0) AS CHAR) AS total_units,COALESCE(SUM(CASE WHEN quantity<=minimum_threshold THEN 1 ELSE 0 END),0) AS low_stock_products FROM products WHERE is_active=1')->fetch();
  return ['active_products'=>(int)$r['active_products'],'total_units'=>$r['total_units'],'low_stock_products'=>(int)$r['low_stock_products'],'out_of_stock_products'=>(int)$this->pdo->query('SELECT COUNT(*) FROM products WHERE is_active=1 AND quantity=0')->fetchColumn()];
 }

 /** Indexed UTC range; Manila has a fixed +08:00 offset throughout the supported reporting range. */
 public function activity(string $from,string $until): array
 {
  $aggregate="CAST(COALESCE(SUM(CASE WHEN type='IN' THEN CAST(quantity AS DECIMAL(30,0)) ELSE 0 END),0) AS CHAR) AS units_in, CAST(COALESCE(SUM(CASE WHEN type='OUT' THEN CAST(quantity AS DECIMAL(30,0)) ELSE 0 END),0) AS CHAR) AS units_out";
  $daily=$this->pdo->prepare("SELECT DATE(DATE_ADD(transaction_date,INTERVAL 8 HOUR)) AS date, {$aggregate} FROM transactions WHERE transaction_date>=? AND transaction_date<? GROUP BY date ORDER BY date");
  $daily->execute([$from,$until]);
  $totals=$this->pdo->prepare("SELECT {$aggregate} FROM transactions WHERE transaction_date>=? AND transaction_date<?");
  $totals->execute([$from,$until]);
  return ['days'=>$daily->fetchAll(),'totals'=>$totals->fetch()];
 }
}
