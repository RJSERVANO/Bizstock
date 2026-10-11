<?php
declare(strict_types=1);
require __DIR__.'/BusinessFixture.php';
use BizStock\Services\{ProductService,StockMovementService};use BizStock\Repositories\HistoryRepository;use BizStock\Support\StockReconciliation;
$f=new BusinessFixture('stage10');try{
 $s=new ProductService($f->pdo);$p=$s->create(['sku'=>'FLOW','product_name'=>'Flow product','category_id'=>1,'supplier_id'=>1,'price'=>'2.50','minimum_threshold'=>3,'opening_quantity'=>5],$f->actor(),'flow-create','stage10');
 $old=$f->product('ARCHIVED');$stock=new StockMovementService($f->pdo);$tx=$stock->record(['product_id'=>$old,'type'=>'IN','quantity'=>1],$f->actor(3),'old-in');$stock->record(['product_id'=>$old,'type'=>'OUT','quantity'=>1],$f->actor(),'old-out');$row=$s->detail($old,'Admin');$s->mutate($old,'DELETE',['expected_version'=>$row['version']],$f->actor(),'stage10');$f->pdo->exec('UPDATE users SET is_active=0 WHERE user_id=3');
 $history=(new HistoryRepository($f->pdo))->listing(['product_id'=>(string)$old,'user_id'=>'3']);$f->check($history['meta']['total']===1&&$history['data'][0]['user_id']===3,'archived/inactive history retained');
 $f->browser('tests/browser/movements.cjs');$row=$s->detail($p['product_id'],'Admin');$f->check($row['quantity']===5,'browser movements/retry/conflict/reversal final balance');
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'browser writes reconcile');echo 'PASS: '.$f->checks.' Stage 10 database assertions.'.PHP_EOL;
}finally{$f->close();}
