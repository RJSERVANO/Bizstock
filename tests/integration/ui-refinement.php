<?php
declare(strict_types=1);
require __DIR__.'/BusinessFixture.php';
$f=new BusinessFixture('ui');
try {
 $f->serve();
 $actor=$f->actor();
 if(($argv[1]??'')==='supplement') {
  $f->browser('tests/browser/ui-supplement.cjs');
  $f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'supplement reconciles');
  return;
 }
 (new BizStock\Services\ProductService($f->pdo))->create(['sku'=>'OFFICE-PAPER','product_name'=>'Everyday copy paper','category_id'=>1,'supplier_id'=>1,'price'=>'245.00','minimum_threshold'=>10,'opening_quantity'=>17],$actor,'ui-paper','ui-paper');
 (new BizStock\Services\ProductService($f->pdo))->create(['sku'=>'OFFICE-PENS','product_name'=>'Ballpoint pens','category_id'=>1,'supplier_id'=>1,'price'=>'12.50','minimum_threshold'=>10,'opening_quantity'=>10],$actor,'ui-pens','ui-pens');
 (new BizStock\Services\ProductService($f->pdo))->create(['sku'=>'OFFICE-FOLDERS','product_name'=>'Document folders','category_id'=>1,'supplier_id'=>1,'price'=>'8.00','minimum_threshold'=>5,'opening_quantity'=>0],$actor,'ui-folders','ui-folders');
 $f->browser('tests/browser/ui-refinement.cjs');
 $f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'UI fixture reconciles');
} finally { $f->close(); }
