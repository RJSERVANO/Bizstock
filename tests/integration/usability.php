<?php
declare(strict_types=1);require __DIR__.'/BusinessFixture.php';
use BizStock\Services\{ProductService,StockMovementService,DashboardService,ProfileService};
use BizStock\Support\StockReconciliation;
$f=new BusinessFixture('usability');$store=$f->config->root.'/var/uploads/'.hash('sha256','test|'.$f->config->dbName);
try{
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'reconciliation before');
 $dashboard=new DashboardService($f->pdo);$profile=new ProfileService($f->pdo);
 $empty=$dashboard->read('Admin');$f->check($empty['out_of_stock_products']===0&&$empty['activity']['totals']===['units_in'=>'0','units_out'=>'0'],'empty real counts/activity');
 foreach([1,2,5] as $id){$before=$f->actor($id);$r=$profile->update(['full_name'=>'  Updated '.$before['role'].'  '],$before,'profile-test');$after=$f->actor($id);$f->check($r['full_name']==='Updated '.$before['role']&&$before['password_hash']===$after['password_hash']&&$before['session_version']===$after['session_version']&&$before['role']===$after['role']&&$before['username']===$after['username'],'own name only all roles');}
 $events=(int)$f->pdo->query("SELECT COUNT(*) FROM audit_events WHERE action='account.profile_updated'")->fetchColumn();$profile->update(['full_name'=>'Updated Admin'],$f->actor(),'profile-noop');$f->check((int)$f->pdo->query('SELECT COUNT(*) FROM audit_events')->fetchColumn()===$events,'same-name save no-op');
 foreach(['',str_repeat('a',101)] as $name)$f->reject(fn()=>$profile->update(['full_name'=>$name],$f->actor(),'bad-name'),422,'bounded nonblank name');
 $f->pdo->exec("CREATE TRIGGER profile_reject_audit BEFORE INSERT ON audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='isolated audit rejection'");try{$profile->update(['full_name'=>'Should roll back'],$f->actor(),'rollback');throw new RuntimeException('accepted audit failure');}catch(PDOException){}finally{$f->pdo->exec('DROP TRIGGER profile_reject_audit');}
 $f->check($f->actor()['full_name']==='Updated Admin','audit failure rolls name back');
 $products=new ProductService($f->pdo);$stock=new StockMovementService($f->pdo);
 foreach([['ZERO',0],['EQUAL',5],['ABOVE',6],['ARCHIVED',0]] as [$sku,$qty])$rows[$sku]=$products->create(['sku'=>$sku,'product_name'=>$sku==='ABOVE'?'Copy paper':$sku,'category_id'=>1,'supplier_id'=>1,'price'=>'2.50','minimum_threshold'=>5,'opening_quantity'=>$qty],$f->actor(),'create:'.$sku,'usability');
 $products->mutate($rows['ARCHIVED']['product_id'],'DELETE',['expected_version'=>1],$f->actor(),'archive');
 $movement=$stock->record(['product_id'=>$rows['EQUAL']['product_id'],'type'=>'IN','quantity'=>2],$f->actor(),'activity-in','usability');
 $stock->record(['correction_of_transaction_id'=>$movement['transaction_id'],'notes'=>'Test correction'],$f->actor(),'activity-reverse','usability');
 $r=$dashboard->read('Admin');$f->check($r['active_products']===3&&$r['low_stock_products']===2&&$r['out_of_stock_products']===1&&$r['total_units']==='11','equality/zero/archived counts');$f->check($r['activity']['totals']===['units_in'=>'13','units_out'=>'2'],'opening and reversal included');
 // Controlled fixture timestamps, never updates on the installed database. Exercise both sides of Manila midnight.
 $today=(new DateTimeImmutable($r['snapshot_at']))->setTimezone(new DateTimeZone('Asia/Manila'))->setTime(0,0);$from=$today->modify('-6 days')->setTimezone(new DateTimeZone('UTC'));$end=$today->modify('+1 day')->setTimezone(new DateTimeZone('UTC'));
 $q=$f->pdo->prepare('UPDATE transactions SET transaction_date=? WHERE transaction_id=?');$tx=$f->pdo->query('SELECT transaction_id FROM transactions ORDER BY transaction_id')->fetchAll(PDO::FETCH_COLUMN);
 foreach([$from->modify('-1 second'),$from,$end->modify('-1 second'),$end] as $i=>$date)$q->execute([$date->format('Y-m-d H:i:s'),$tx[$i]]);
 foreach([7,30] as $period){$r=$dashboard->read('Admin',$period);$a=$r['activity'];$f->check(count($a['days'])===$period&&$a['date_to']===$today->format('Y-m-d'),'bounded zero-filled calendar');
  $start=$today->modify('-'.($period-1).' days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$until=$end->format('Y-m-d H:i:s');
  $q=$f->pdo->prepare("SELECT CAST(COALESCE(SUM(IF(type='IN',quantity,0)),0) AS CHAR) AS units_in,CAST(COALESCE(SUM(IF(type='OUT',quantity,0)),0) AS CHAR) AS units_out FROM transactions WHERE transaction_date>=? AND transaction_date<?");$q->execute([$start,$until]);$f->check($a['totals']===$q->fetch(),'independent complete SQL totals');
  foreach($a['days'] as $day){$begin=(new DateTimeImmutable($day['date'],new DateTimeZone('Asia/Manila')))->setTimezone(new DateTimeZone('UTC'));$q->execute([$begin->format('Y-m-d H:i:s'),$begin->modify('+1 day')->format('Y-m-d H:i:s')]);$f->check(array_diff_assoc($q->fetch(),$day)===[],'independent daily Manila boundary');}
 }
 $f->reject(fn()=>$dashboard->read('Admin',8),422,'bounded activity period');$v=$dashboard->read('Viewer');$f->check(!isset($v['activity'])&&!isset($v['recent_movements'])&&!str_contains(json_encode($v),'unit_price'),'Viewer projection omits activity and sensitive fields');
 // Archived products and inactive actors still contribute to activity, but never to live stock counts.
 $archived=$products->create(['sku'=>'HISTORICAL','product_name'=>'Historical stock','category_id'=>1,'supplier_id'=>1,'price'=>'1.00','minimum_threshold'=>0,'opening_quantity'=>3],$f->actor(),'historical-create','usability');
 $stock->record(['product_id'=>$archived['product_id'],'type'=>'OUT','quantity'=>3],$f->actor(3),'historical-out');$current=$f->pdo->query('SELECT version FROM products WHERE product_id='.(int)$archived['product_id'])->fetch();
 $products->mutate($archived['product_id'],'DELETE',['expected_version'=>(int)$current['version']],$f->actor(),'historical-archive');$f->pdo->exec('UPDATE users SET is_active=0 WHERE user_id=3');
 $historical=$dashboard->read('Admin');$f->check($historical['active_products']===3&&$historical['activity']['totals']['units_out']==='3','archived product inactive actor activity retained');
 $plan=$f->pdo->query("EXPLAIN SELECT transaction_id FROM transactions FORCE INDEX (idx_transactions_date) WHERE transaction_date>='2026-01-01 00:00:00' AND transaction_date<'2027-01-01 00:00:00'")->fetch();file_put_contents($f->config->root.'/docs/visual/usability/index-evidence.json',json_encode($plan,JSON_PRETTY_PRINT));$f->check($plan['key']==='idx_transactions_date'&&in_array($plan['type'],['range','index'],true),'existing UTC range index eligible; no new migration');
 $f->serve();$none=[];$f->check($f->http('/api/v1/account/profile',$none,'PATCH',['full_name'=>'Anonymous'])[0]===401,'anonymous profile denied');
 foreach([1,2,5] as $id){$jar=$f->login($id);$f->check($f->http('/api/v1/account/profile',$jar,'PATCH',['full_name'=>'No token'])[0]===403,'profile CSRF required');foreach(['role'=>'Admin','is_active'=>false,'username'=>'other','user_id'=>4] as $key=>$value)$f->check($f->http('/api/v1/account/profile',$jar,'PATCH',['full_name'=>'Tamper',$key=>$value],$jar['token'])[0]===422,'profile rejects authority/target field '.$key);$f->check($f->http('/api/v1/account/profile',$jar,'PATCH',['full_name'=>'Saved '.$id],$jar['token'])[0]===200,'real own profile PATCH');$f->check($f->http('/api/v1/account/profile/4',$jar,'PATCH',['full_name'=>'Target'],$jar['token'])[0]===404,'other-account profile route absent');}
 $viewer=$f->login(5);$f->check($f->http('/api/v1/dashboard?period=7',$viewer)[0]===403,'Viewer cannot request activity');$admin=$f->login();$f->check($f->http('/api/v1/dashboard?period=8',$admin)[0]===422,'HTTP period validation');
 $f->pdo->exec('UPDATE users SET must_change_password=1 WHERE user_id=5');$f->check($f->http('/api/v1/account/profile',$viewer,'PATCH',['full_name'=>'Forbidden'],$viewer['token'])[0]===403,'forced-change profile denied');$f->pdo->exec('UPDATE users SET must_change_password=0 WHERE user_id=5');
 $f->check($f->http('/login?role=Owner',$none)[0]===422,'invalid public role hint rejected');$f->check($f->http('/login?role=Staff',$none)[0]===200,'validated public hint accepted');
 $image=imagecreatetruecolor(48,48);$photo=$f->config->root.'/var/'.$f->config->dbName.'.png';imagepng($image,$photo);putenv('IMAGE_FIXTURE_FILE='.$photo);
 $f->browser('tests/browser/usability.cjs');
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'reconciliation after all browser mutations');echo 'PASS: '.$f->checks.' usability MariaDB/HTTP assertions.'.PHP_EOL;
}finally{if(isset($photo)&&is_file($photo))unlink($photo);if(is_dir($store)){foreach(glob($store.'/*.webp') as $file)unlink($file);rmdir($store);}$f->close();}
