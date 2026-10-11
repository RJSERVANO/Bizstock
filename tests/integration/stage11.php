<?php
declare(strict_types=1);require __DIR__.'/BusinessFixture.php';
use BizStock\Services\{ProductService,StockMovementService,DashboardService};use BizStock\Support\{ReadSnapshot,StockReconciliation};use BizStock\Repositories\DashboardRepository;
$f=new BusinessFixture('stage11');try{
 $dashboard=new DashboardService($f->pdo);$f->check($dashboard->read('Viewer')['active_products']===0,'empty dashboard');$products=new ProductService($f->pdo);$stock=new StockMovementService($f->pdo);
 foreach([['ZERO',0],['EQUAL',5],['ABOVE',6],['ARCHIVED',0]] as [$sku,$qty]){$rows[$sku]=$products->create(['sku'=>$sku,'product_name'=>$sku,'category_id'=>1,'supplier_id'=>1,'price'=>'2.50','minimum_threshold'=>5,'opening_quantity'=>$qty],$f->actor(),'create:'.$sku,'stage11');}
 $products->mutate($rows['ARCHIVED']['product_id'],'DELETE',['expected_version'=>1],$f->actor(),'stage11');$r=$dashboard->read('Admin');$f->check($r['active_products']===3&&$r['total_units']==='11'&&$r['low_stock_products']===2,'zero/equality/archive counts');$f->check(count($r['low_stock'])===2&&$r['low_stock'][0]['stock_status']==='out_of_stock','zero distinct from equality');
 $viewer=$dashboard->read('Viewer');$f->check(!isset($viewer['recent_movements'],$viewer['low_stock'][0]['price'],$viewer['low_stock'][0]['supplier_id']),'nested Viewer allowlist');
 $f->check(count($dashboard->read('Staff')['recent_movements'])===2,'Staff recent movements');
 ReadSnapshot::run($f->pdo,function()use($f,$rows){$before=(new DashboardRepository($f->pdo))->counts();$result=$f->workers([['actor'=>2,'key'=>'snapshot-race','body'=>['product_id'=>$rows['ABOVE']['product_id'],'type'=>'OUT','quantity'=>1]]]);$after=(new DashboardRepository($f->pdo))->counts();$list=(new ProductService($f->pdo))->listing(['low_stock'=>'true'],'Viewer');$f->check($result[0]['status']===201&&$before===$after&&$list['meta']['total']===2,'snapshot consistent across concurrent committed mutation');return [];});
 $r=$dashboard->read('Viewer');$f->check($r['total_units']==='10'&&$r['low_stock_products']===3,'fresh snapshot after OUT');$f->check($products->listing(['low_stock'=>'true'],'Viewer')['meta']['total']===$r['low_stock_products'],'dashboard alert filter parity');
 $f->serve();$viewer=$f->login(5);[$status,$r]=$f->http('/api/v1/dashboard',$viewer);$f->check($status===200&&!isset($r['data']['recent_movements']),'actual Viewer dashboard HTTP');
 $f->browser('tests/browser/dashboard.cjs');
 foreach(['BIG1','BIG2'] as $sku){$products->create(['sku'=>$sku,'product_name'=>$sku,'category_id'=>1,'supplier_id'=>1,'price'=>'0.00','minimum_threshold'=>0,'opening_quantity'=>2147483647],$f->actor(),'create:'.$sku,'stage11');}
 $f->check($dashboard->read('Viewer')['total_units']==='4294967305','wide total units without float/INT overflow');$f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'dashboard fixture reconciles');echo 'PASS: '.$f->checks.' Stage 11 database assertions.'.PHP_EOL;
}finally{$f->close();}

