<?php
declare(strict_types=1);require __DIR__.'/BusinessFixture.php';
use BizStock\Services\{StockMovementService,ProductService,StockCountService};
$f=new BusinessFixture('workspaceui');try{
 $a=$f->actor();$p=new ProductService($f->pdo);$stock=new StockMovementService($f->pdo);$counts=new StockCountService($f->pdo);
 foreach([['UI-001','Warehouse storage containers with reinforced handles and a deliberately long product name','box',20],['UI-002','Cable ties','piece',60]] as [$sku,$name,$unit,$quantity]){
  $product=$p->create(['sku'=>$sku,'product_name'=>$name,'category_id'=>1,'supplier_id'=>1,'price'=>'12.50','minimum_threshold'=>5,'opening_quantity'=>$quantity,'unit_code'=>$unit],$a,$sku,'ui-fixture');
  $stock->record(['product_id'=>$product['product_id'],'type'=>'OUT','quantity'=>2,'reason_code'=>'sale_release'],$a,'out-'.$sku);
 }
 $f->product('UI-QUIET');
 foreach(['draft','submitted','approved','cancelled'] as $status){
  $count=$counts->create(['note'=>ucfirst($status).' shelf review'],$a,'ui-'.$status,'ui-fixture');
  $rev=(int)$f->pdo->query('SELECT stock_revision FROM products WHERE product_id=1')->fetchColumn();
  $count=$counts->saveLine($count['count_id'],1,['expected_version'=>$count['version'],'expected_stock_revision'=>$rev,'counted_quantity'=>18,'reason'=>null],$a,'ui-fixture');
  if(in_array($status,['submitted','approved'],true))$count=$counts->transition($count['count_id'],'submit',['expected_version'=>$count['version']],$a,'','ui-fixture');
  if($status==='approved')$counts->transition($count['count_id'],'approve',['expected_version'=>$count['version']],$a,'ui-approve','ui-fixture');
  if($status==='cancelled')$counts->transition($count['count_id'],'cancel',['expected_version'=>$count['version']],$a,'','ui-fixture');
 }
 $f->pdo->exec('UPDATE users SET must_change_password=1 WHERE user_id=4');
 $f->serve();$f->browser('tests/browser/workspace-ui.cjs');echo 'PASS: disposable workspace UI fixture. No installed target used.'.PHP_EOL;
}finally{$f->close();}
