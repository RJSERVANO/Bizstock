<?php
declare(strict_types=1);require __DIR__.'/BusinessFixture.php';
use BizStock\Services\{ProductService,StockMovementService,DashboardService};
$f=new BusinessFixture('dash_refine');try{
 $products=new ProductService($f->pdo);$stock=new StockMovementService($f->pdo);$ids=[];
 foreach([0,0,1,5,5,6,0,3] as $i=>$qty){$p=$products->create(['sku'=>'REF-'.($i+1),'product_name'=>'Refinement item '.($i+1),'category_id'=>1,'supplier_id'=>1,'price'=>'2.50','minimum_threshold'=>5,'opening_quantity'=>$qty],$f->actor(),'ref-create-'.$i,'refinement');$ids[]=$p['product_id'];}
 $products->mutate($ids[6],'DELETE',['expected_version'=>1],$f->actor(),'refinement');
 for($i=0;$i<30;$i++)$stock->record(['product_id'=>$ids[5],'type'=>$i%2?'OUT':'IN','quantity'=>1],$f->actor(),'ref-move-'.$i,'refinement');
 $insert=$f->pdo->prepare("INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,'Viewer',0)");for($i=0;$i<30;$i++)$insert->execute(['List member '.$i,'member'.$i,BizStock\Support\PasswordPolicy::hash($f->password)]);
 $f->serve();if(getenv('REFINEMENT_PHASE')!=='before'){
  $query=$f->pdo->query('SELECT COUNT(*),SUM(quantity),SUM(quantity<=minimum_threshold),SUM(quantity=0) FROM products WHERE is_active=1')->fetch(PDO::FETCH_NUM);
  foreach([1=>'Admin',2=>'Staff',5=>'Viewer'] as $id=>$role){$jar=$f->login($id);[$status,$r]=$f->http('/api/v1/dashboard'.($role==='Viewer'?'':'?period=30'),$jar);$d=$r['data'];$f->check($status===200&&$d['active_products']==$query[0]&&$d['total_units']==$query[1]&&$d['low_stock_products']==$query[2]&&$d['out_of_stock_products']==$query[3],$role.' independent counts');$expected=$f->pdo->query('SELECT product_id FROM products WHERE is_active=1 AND quantity<=minimum_threshold ORDER BY quantity ASC,product_id ASC LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);$f->check(array_column($d['low_stock'],'product_id')===$expected,$role.' deterministic zero/equality/archive alerts');
   if($role==='Viewer'){$f->check(!isset($d['activity'],$d['recent_movements'])&&!str_contains(json_encode($d),'"price"')&&!str_contains(json_encode($d),'supplier'), 'Viewer restricted fields absent');}else{$f->check(count($d['activity']['days'])===30,'zero days included');foreach($d['activity']['days'] as $day){$q=$f->pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type='IN' THEN quantity ELSE 0 END),0),COALESCE(SUM(CASE WHEN type='OUT' THEN quantity ELSE 0 END),0) FROM transactions WHERE DATE(DATE_ADD(transaction_date,INTERVAL 8 HOUR))=?");$q->execute([$day['date']]);$v=$q->fetch(PDO::FETCH_NUM);$f->check($day['units_in']==$v[0]&&$day['units_out']==$v[1],$role.' independent Manila daily values');}}
  }
 }
 $f->browser('tests/browser/dashboard-refinement.cjs');
 $f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'fixture reconciles after browser');
 if(getenv('REFINEMENT_PHASE')!=='before'){$empty=new BusinessFixture('dash_empty');try{$d=(new DashboardService($empty->pdo))->read('Admin',30);$f->check($d['active_products']===0&&$d['total_units']==='0'&&$d['activity']['totals']===['units_in'=>'0','units_out'=>'0']&&count($d['activity']['days'])===30,'real empty inventory and 30-day period');putenv('REFINEMENT_EMPTY=1');$empty->browser('tests/browser/dashboard-refinement.cjs');}finally{putenv('REFINEMENT_EMPTY');$empty->close();}}
 echo 'PASS: '.$f->checks.' dashboard refinement database assertions.'.PHP_EOL;
}finally{$f->close();}

