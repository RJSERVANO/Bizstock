<?php
declare(strict_types=1);
require __DIR__.'/BusinessFixture.php';
use BizStock\Support\{ProductListState,StockReconciliation};
use BizStock\Services\StockMovementService;
use BizStock\Repositories\AuditRepository;
$f=new BusinessFixture('phase12');
try{
 $actor=$f->actor();$stock=new StockMovementService($f->pdo);
 for($i=1;$i<=28;$i++){$id=$f->product('PHASE12-'.str_pad((string)$i,2,'0',STR_PAD_LEFT),'2.50',3);if($i===1)$stock->record(['product_id'=>$id,'type'=>'IN','quantity'=>10],$actor,'phase12-opening');}
 $archived=$f->product('ARCHIVED-PHASE12');$f->pdo->prepare('UPDATE products SET is_active=0 WHERE product_id=?')->execute([$archived]);
 for($i=0;$i<28;$i++)$f->pdo->prepare("INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,'Staff',0)")->execute(['Filter staff '.$i,'filter'.$i,$actor['password_hash']]);
 $audit=new AuditRepository($f->pdo);$audit->record(1,'product.updated','product',1,['product_name'=>'Before','description'=>'old'],['product_name'=>'<img src=x onerror=alert(1)>','description'=>'<script>unsafe</script>'],'phase12-safe');
 $audit->record(1,'legacy.changed','product',1,['legacy','old'],['legacy','new'],'phase12-legacy');
 $f->check(ProductListState::destination('/bizstock/products?q=PHASE12&page=2&per_page=1',$f->config,'Admin')==='/bizstock/products?q=PHASE12&page=2&per_page=1','approved return state');
 foreach(['https://evil.test/products','//evil.test/products','/bizstock/products/../users','/bizstock/products?sort=evil','/bizstock/products?page=-1','/bizstock/products?q=a&q=b',"/bizstock/products?x=1",'/bizstock/products#fragment',"/bizstock/products\\evil"] as $bad)$f->check(ProductListState::destination($bad,$f->config,'Admin')==='/bizstock/products','unsafe return refused');
 $stock->record(['product_id'=>3,'type'=>'IN','quantity'=>3],$actor,'phase12-equality');
 $f->serve();$admin=$f->login();$staff=$f->login(2);$viewer=$f->login(5);
 foreach([['categories','category_id','Viewer'],['suppliers','supplier_id','Staff']] as [$resource,$field,$role]){
  $jar=$role==='Viewer'?$viewer:$staff;[$status,$r]=$f->http('/api/v1/'.$resource.'?include_products=true',$jar);$f->check($status===200,'count authorized');$row=$r['data'][0];
  foreach(['active','archived'] as $scope){[$status,$products]=$f->http('/api/v1/products?'.$field.'=1&active='.$scope,$jar);$f->check($status===200&&$products['meta']['total']===$row[$scope.'_product_count'],'count matches destination '.$scope);}
 }
 [$status,$dashboard]=$f->http('/api/v1/dashboard',$admin);[$status,$alerts]=$f->http('/api/v1/products?low_stock=true',$admin);[$status,$low]=$f->http('/api/v1/products?stock_status=low_stock',$admin);[$status,$zero]=$f->http('/api/v1/products?stock_status=out_of_stock',$admin);$f->check($dashboard['data']['low_stock_products']===$alerts['meta']['total']&&$alerts['meta']['total']===$low['meta']['total']+$zero['meta']['total']&&$low['meta']['total']===1,'inclusive alerts and exclusive statuses parity');
 [$status,$plain]=$f->http('/api/v1/categories',$viewer);$f->check($status===200&&array_keys($plain['data'][0])===['category_id','category_name'],'legacy category projection unchanged');
 [$status,$users]=$f->http('/api/v1/users?q=filter&role=Staff&is_active=true&sort=role&direction=desc&per_page=2&page=2',$admin);$f->check($status===200&&$users['meta']['total']===28&&count($users['data'])===2&&isset($users['data'][0]['created_at']),'combined users filters pagination timestamps');
 [$status]=$f->http('/api/v1/users?role=staff',$admin);$f->check($status===422,'case-sensitive role filter');
 [$status,$events]=$f->http('/api/v1/audit-events?action=product.updated&actor_id=1&entity_type=product&entity_id=1&per_page=1',$admin);$f->check($status===200&&$events['meta']['total']===1&&$events['data'][0]['actor_name']==='Admin 1','bounded audit action and actor projection');
 foreach(['action='.str_repeat('a',61),'action=%25','action=product.updated&action=user.updated'] as $bad){[$status]=$f->http('/api/v1/audit-events?'.$bad,$admin);$f->check($status===422,'invalid audit action rejected');}
 foreach([['/api/v1/users?role=Staff',$staff],['/api/v1/audit-events?action=product.updated',$staff],['/api/v1/suppliers?include_products=true',$viewer],['/api/v1/transactions?product_id=1',$viewer],['/api/v1/products?supplier_id=1',$viewer]] as [$path,$jar]){[$status]=$f->http($path,$jar);$f->check($status===403,'direct forbidden API');}
 foreach([$admin,$staff,$viewer] as $jar){[$status]=$f->http('/help',$jar);$f->check($status===200,'role-specific help available');}
 [$status,$details]=$f->http('/api/v1/products/1',$viewer);$f->check($status===200&&!isset($details['data']['price'],$details['data']['supplier_name']),'Viewer details projection');
 $f->browser('tests/browser/phase12.cjs');
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'phase12 fixture reconciles');
 echo 'PASS: '.$f->checks." Phase 1–2 DB/HTTP/harness assertions.\n";
}finally{$f->close();}
