<?php
declare(strict_types=1);
require __DIR__.'/BusinessFixture.php';
use BizStock\Services\{ProductService,StockMovementService};
use BizStock\Support\StockReconciliation;
$f=new BusinessFixture('stage9');
try{
 $s=new ProductService($f->pdo);$a=$f->actor();$body=['sku'=>'sku-1','product_name'=>'Product one','category_id'=>1,'supplier_id'=>1,'price'=>'3.25','minimum_threshold'=>3,'opening_quantity'=>5];
 $p=$s->create($body,$a,'create','test');$id=$p['product_id'];$f->check($p['quantity']===5&&$p['sku']==='SKU-1'&&$p['version']===2,'atomic opening balance');
 $f->check($s->create($body,$a,'create','retry')===$p,'original creation result replay');$f->reject(fn()=>$s->create(array_replace($body,['product_name'=>'Changed']),$a,'create','test'),409,'changed create payload');
 $f->reject(fn()=>(new StockMovementService($f->pdo))->record(['product_id'=>$id,'type'=>'IN','quantity'=>5],$a,'create'),409,'global actor key namespace');
 $f->reject(fn()=>$s->create($body,$a,'different','test'),409,'canonical unique SKU');
 $f->reject(fn()=>$s->create(array_replace($body,['sku'=>' SKU-1 ']),$a,'spaces','test'),409,'SKU trim/case identity');
 $zero=$f->product('ZERO-EDIT');$renamed=$s->mutate($zero,'PATCH',['sku'=>'zero-renamed','expected_version'=>1],$a,'test');$f->check($renamed['sku']==='ZERO-RENAMED'&&$renamed['quantity']===0,'zero-history SKU edit remains metadata only');
 foreach([['category_id'=>999],['supplier_id'=>999],['price'=>'-1.00'],['minimum_threshold'=>-1],['quantity'=>5],['opening_quantity'=>-1]] as $bad){$f->reject(fn()=>$s->create(array_replace($body,['sku'=>'BAD'],$bad),$a,'bad','test'),422,'invalid create');}
 $f->reject(fn()=>$s->mutate($id,'PATCH',['category_id'=>null,'product_name'=>'No','expected_version'=>2],$a,'test'),422,'null required FK');
 $f->reject(fn()=>$s->mutate($id,'PATCH',['quantity'=>9,'expected_version'=>2],$a,'test'),422,'quantity patch blocked');
 $f->reject(fn()=>$s->mutate($id,'PATCH',['sku'=>'NEW','expected_version'=>2],$a,'test'),409,'SKU immutable after movement');
 $f->reject(fn()=>$s->mutate($id,'DELETE',['expected_version'=>2],$a,'test'),409,'nonzero archive blocked');
 $edited=$s->mutate($id,'PATCH',['price'=>'8.00','location'=>'Shelf A','expected_version'=>2],$a,'test');$f->check($edited['version']===3&&$edited['price']==='8.00','metadata/version edit');
 $f->reject(fn()=>$s->mutate($id,'PATCH',['price'=>'1.00','expected_version'=>2],$a,'test'),409,'stale edit');
 $f->check($f->pdo->query('SELECT unit_price FROM transactions WHERE product_id='.$id)->fetchColumn()==='3.25','opening price preserved');
 foreach([2,5] as $actor){$f->reject(fn()=>$s->create($body,$f->actor($actor),'denied','test'),403,'non Admin create denied');}
 $view=$s->detail($id,'Viewer');$f->check(!isset($view['price'],$view['supplier_name'],$view['location'],$view['version'])&&$view['quantity']===5,'Viewer positive fields');
 $f->reject(fn()=>$s->listing(['supplier_id'=>'1'],'Viewer'),403,'Viewer supplier filter');
 $f->reject(fn()=>$s->listing(['sort'=>'price'],'Viewer'),403,'Viewer price sorting');
 $f->pdo->exec("CREATE TRIGGER fail_audit BEFORE INSERT ON audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='failure'");
 try{$s->create(array_replace($body,['sku'=>'ROLLBACK']),$a,'rollback','test');throw new RuntimeException('Expected rollback');}catch(PDOException){}
 $f->pdo->exec('DROP TRIGGER fail_audit');$f->check((int)$f->pdo->query("SELECT COUNT(*) FROM products WHERE sku='ROLLBACK'")->fetchColumn()===0&&(int)$f->pdo->query("SELECT COUNT(*) FROM operation_requests WHERE request_key='rollback'")->fetchColumn()===0,'audit failure rolls back product/opening/reservation');
 $jobs=[];foreach([1,1] as $actor){$jobs[]=['action'=>'create','actor'=>$actor,'key'=>'parallel','body'=>array_replace($body,['sku'=>'PARALLEL'])];}$r=$f->workers($jobs);$f->check($r[0]['result']===$r[1]['result'],'concurrent creation retry one result');
 $jobs=[];foreach([1,4] as $actor){$jobs[]=['action'=>'create','actor'=>$actor,'key'=>'unique-race','body'=>array_replace($body,['sku'=>'UNIQUE'])];}$r=$f->workers($jobs);$statuses=array_column($r,'status');sort($statuses);$f->check($statuses===[201,409],'concurrent SKU unique');
 $jobs=[];foreach([1,4] as $actor){$jobs[]=['action'=>'edit','actor'=>$actor,'product'=>$id,'body'=>['product_name'=>'Editor '.$actor,'expected_version'=>3]];}$r=$f->workers($jobs);$statuses=array_column($r,'status');sort($statuses);$f->check($statuses===[201,409],'concurrent editors detect stale version');
 foreach([false,true] as $first){$race=$f->product($first?'ARCHIVE-FIRST':'STOCK-FIRST');$r=$f->workers([['action'=>'archive','actor'=>4,'product'=>$race,'version'=>1,'delay'=>$first?0:300000],['actor'=>2,'key'=>'race:'.$race,'body'=>['product_id'=>$race,'type'=>'IN','quantity'=>1],'delay'=>$first?300000:0]]);$statuses=array_column($r,'status');sort($statuses);$f->check($statuses===[201,409],'real archive versus movement both orderings');}
 (new StockMovementService($f->pdo))->record(['product_id'=>$id,'type'=>'OUT','quantity'=>5],$a,'empty');$current=$s->detail($id,'Admin');$archived=$s->mutate($id,'DELETE',['expected_version'=>$current['version']],$a,'test');
 $f->check(!$archived['is_active']&&(int)$f->pdo->query('SELECT COUNT(*) FROM transactions WHERE product_id='.$id)->fetchColumn()===2,'archive preserves history');
 $f->check($s->listing(['active'=>'archived'],'Admin')['meta']['total']===2,'archived filter');
 $f->reject(fn()=>$s->create($body,$a,'archived-sku','test'),409,'archived SKU remains reserved');
 $f->serve();$admin=$f->login();$staff=$f->login(2);$viewer=$f->login(5);
 [$status,$json]=$f->http('/api/v1/products',$admin,'POST',array_replace($body,['sku'=>'HTTP']),$admin['token'],'http-create');$f->check($status===201&&$json['data']['quantity']===5,'HTTP creation/opening');
 [$status,$json]=$f->http('/api/v1/products?category_id=1&stock_status=in_stock',$viewer);$f->check($status===200&&!isset($json['data'][0]['price']),'HTTP availability filters/projector');
 [$status]=$f->http('/api/v1/products/'.$id,$staff,'PATCH',['price'=>'1.00','expected_version'=>1],$staff['token']);$f->check($status===403,'HTTP Staff metadata forbidden');
 [$status]=$f->http('/api/v1/products?sort=price',$viewer);$f->check($status===403,'HTTP restricted sorting');
 $f->browser('tests/browser/products.cjs');
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'creation/movements/archive reconcile');
 echo 'PASS: '.$f->checks.' Stage 9 database assertions.'.PHP_EOL;
}finally{$f->close();}
