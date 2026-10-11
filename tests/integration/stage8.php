<?php
declare(strict_types=1);
require __DIR__.'/BusinessFixture.php';
use BizStock\Services\StockMovementService;
use BizStock\Support\StockReconciliation;
use BizStock\Repositories\HistoryRepository;
$f=new BusinessFixture('stage8');
try{
    $s=new StockMovementService($f->pdo);$p=$f->product();$a=$f->actor();
    $in=['product_id'=>$p,'type'=>'IN','quantity'=>10];$first=$s->record($in,$a,'in');$f->check($first['stock']['quantity']===10,'IN commits movement/balance');
    $f->check($s->record($in,$a,'in')===$first,'same key replays original result');$f->reject(fn()=>$s->record($in+['notes'=>'changed'],$a,'in'),409,'changed payload key conflict');
    $out=$s->record(['product_id'=>$p,'type'=>'OUT','quantity'=>4],$f->actor(2),'out');$f->check($out['stock']['quantity']===6,'OUT balance');
    $f->reject(fn()=>$s->record(['product_id'=>$p,'type'=>'OUT','quantity'=>7],$a,'insufficient'),409,'insufficient stock');
    foreach([0,-1,2147483648,1.5] as $q){$f->reject(fn()=>$s->record(['product_id'=>$p,'type'=>'IN','quantity'=>$q],$a,'invalid'),422,'quantity bounds');}
    $f->reject(fn()=>$s->record($in+['unit_price'=>'0.00'],$a,'forged'),422,'no price override');$f->reject(fn()=>$s->record($in,$f->actor(5),'viewer'),403,'Viewer write denied');
    $originalBefore=$f->pdo->query('SELECT * FROM transactions WHERE transaction_id='.$out['transaction_id'])->fetch();
    $f->pdo->exec('UPDATE products SET price=9.00 WHERE product_id='.$p);$reverse=['correction_of_transaction_id'=>$out['transaction_id'],'notes'=>'Reverse example'];
    $f->reject(fn()=>$s->record($reverse,$f->actor(2),'staff-reverse'),403,'Staff reversal denied');$r=$s->record($reverse,$a,'reverse');$f->check($r['unit_price']==='2.50'&&$r['stock']['quantity']===10,'original captured price reversal');
    $f->check($originalBefore===$f->pdo->query('SELECT * FROM transactions WHERE transaction_id='.$out['transaction_id'])->fetch(),'full original movement row unchanged by price edit and reversal');
    $f->reject(fn()=>$s->record($reverse,$a,'repeat-reverse'),409,'one reversal');$f->reject(fn()=>$s->record(['correction_of_transaction_id'=>$r['transaction_id'],'notes'=>'bad'],$a,'reverse-reverse'),409,'no reversal of reversal');
    $f->pdo->exec("CREATE TRIGGER fail_movement BEFORE INSERT ON transactions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='failure'");
    try{$s->record($in,$a,'rollback');throw new RuntimeException('No rollback');}catch(PDOException){}
    $f->pdo->exec('DROP TRIGGER fail_movement');$f->check((int)$f->pdo->query("SELECT quantity FROM products WHERE product_id={$p}")->fetchColumn()===10&&(int)$f->pdo->query("SELECT COUNT(*) FROM operation_requests WHERE request_key='rollback'")->fetchColumn()===0,'movement failure rolls back reservation/balance');
    $f->pdo->exec('UPDATE products SET is_active=0 WHERE product_id='.$p);$f->reject(fn()=>$s->record($in,$a,'archived'),409,'inactive product');$f->pdo->exec('UPDATE products SET is_active=1 WHERE product_id='.$p);
    $big=$f->product('BIG');$s->record(['product_id'=>$big,'type'=>'IN','quantity'=>2147483647],$a,'max');$f->reject(fn()=>$s->record(['product_id'=>$big,'type'=>'IN','quantity'=>1],$a,'overflow'),409,'stock overflow');
    $limited=$f->product('LIMIT');$s->record(['product_id'=>$limited,'type'=>'IN','quantity'=>5],$a,'opening-limit');
    $jobs=[];foreach([2,3] as $id){$jobs[]=['actor'=>$id,'key'=>'race-out','body'=>['product_id'=>$limited,'type'=>'OUT','quantity'=>4]];}$results=$f->workers($jobs);$statuses=array_column($results,'status');sort($statuses);$f->check($statuses===[201,409],'two connections cannot oversell');
    $duplicate=$f->workers([['actor'=>2,'key'=>'parallel','body'=>['product_id'=>$limited,'type'=>'IN','quantity'=>2]],['actor'=>2,'key'=>'parallel','body'=>['product_id'=>$limited,'type'=>'IN','quantity'=>2]]]);$f->check($duplicate[0]['result']===$duplicate[1]['result'],'parallel duplicate one effect');
    $reverseRace=$f->workers([['actor'=>1,'key'=>'reverse-race-a','body'=>['correction_of_transaction_id'=>$first['transaction_id'],'notes'=>'race']],['actor'=>4,'key'=>'reverse-race-b','body'=>['correction_of_transaction_id'=>$first['transaction_id'],'notes'=>'race']]]);$statuses=array_column($reverseRace,'status');sort($statuses);$f->check($statuses===[201,409],'parallel reversal unique/current-read protection');
    $deadlock=$f->workers([['actor'=>2,'other'=>3,'action'=>'deadlock'],['actor'=>3,'other'=>2,'action'=>'deadlock']]);$f->check(max(array_column(array_column($deadlock,'result'),'attempts'))>=2,'actual InnoDB deadlock retries entire transaction');
    $f->pdo->beginTransaction();$f->pdo->query('SELECT user_id FROM users WHERE user_id=1 FOR UPDATE');$timeout=$f->workers([['actor'=>1,'key'=>'timeout','body'=>$in]]);$f->pdo->rollBack();$f->check($timeout[0]['status']===503,'three bounded lock-timeout attempts');
    $history=(new HistoryRepository($f->pdo))->detail($out['transaction_id']);$f->check($history['reversed_by_transaction_id']===$r['transaction_id']&&$history['unit_price']==='2.50'&&!isset($history['payload_hash']),'immutable detail with correction links');
    foreach([false,true] as $archiveFirst){$race=$f->product($archiveFirst?'ARC-FIRST':'IN-FIRST');$results=$f->workers([['actor'=>4,'action'=>'archive-probe','product'=>$race,'delay'=>$archiveFirst?0:300000],['actor'=>2,'key'=>'archive-race:'.$race,'body'=>['product_id'=>$race,'type'=>'IN','quantity'=>1],'delay'=>$archiveFirst?300000:0]]);$statuses=array_column($results,'status');sort($statuses);$f->check($statuses===[201,409],'archive locking probe both orderings');}
    $f->serve();$jar=$f->login(2);$viewer=$f->login(5);
    [$status,$json]=$f->http('/api/v1/transactions',$jar,'POST',['product_id'=>$p,'type'=>'IN','quantity'=>2],$jar['token'],'http-stock');$f->check($status===201&&$json['data']['stock']['quantity']===2,'real transaction HTTP');
    [$status]=$f->http('/api/v1/transactions/'.$json['data']['transaction_id'],$jar,'PATCH',[],$jar['token']);$f->check($status===405,'no history mutation API');
    [$status,$json]=$f->http('/api/v1/transactions?product_id='.$p,$jar);$f->check($status===200&&$json['meta']['total']===5,'real history filters and envelope');
    [$status]=$f->http('/api/v1/transactions',$viewer);$f->check($status===403,'Viewer history denial');
    $oldActor=$f->actor(3);$f->pdo->exec('UPDATE users SET is_active=0 WHERE user_id=3');$f->reject(fn()=>$s->record($in,$oldActor,'revoked'),401,'writer rechecks inactive actor under lock');
    $f->pdo->exec("UPDATE users SET is_active=1,role='Viewer' WHERE user_id=3");$f->reject(fn()=>$s->record($in,$oldActor,'demoted'),403,'writer rejects stale supplied role');
    $f->pdo->exec("UPDATE users SET role='Staff',must_change_password=1 WHERE user_id=3");$f->reject(fn()=>$s->record($in,$oldActor,'forced'),403,'writer rechecks forced change');
    $f->pdo->exec('UPDATE users SET must_change_password=0,session_version=session_version+1 WHERE user_id=3');$f->reject(fn()=>$s->record($in,$oldActor,'stale-session'),401,'writer rechecks session version');
    $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'all balances reconcile after races');
    // Review regressions use separate products; failure occurs after the movement INSERT.
    $probe=$f->product('REVIEW');$probeIn=$s->record(['product_id'=>$probe,'type'=>'IN','quantity'=>2],$a,'review-in');
    $f->pdo->exec("CREATE TRIGGER fail_balance BEFORE UPDATE ON products FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='failure'");
    try{$s->record(['product_id'=>$probe,'type'=>'OUT','quantity'=>1],$a,'review-rollback');throw new RuntimeException('No balance rollback');}catch(PDOException){}
    finally{$f->pdo->exec('DROP TRIGGER fail_balance');}
    $f->check((int)$f->pdo->query("SELECT quantity FROM products WHERE product_id={$probe}")->fetchColumn()===2&&(int)$f->pdo->query("SELECT COUNT(*) FROM transactions WHERE request_key='review-rollback'")->fetchColumn()===0&&(int)$f->pdo->query("SELECT COUNT(*) FROM operation_requests WHERE request_key='review-rollback'")->fetchColumn()===0,'post-insert balance failure rolls back movement/key/balance');
    $s->record(['product_id'=>$probe,'type'=>'OUT','quantity'=>1],$a,'review-rollback');
    $f->check((int)$f->pdo->query("SELECT COUNT(*) FROM transactions WHERE request_key='review-rollback'")->fetchColumn()===1,'rolled-back key safely reusable');
    foreach([null,'','   '] as $reason){$f->reject(fn()=>$s->record(['correction_of_transaction_id'=>$probeIn['transaction_id'],'notes'=>$reason],$a,'review-reason'),422,'reversal requires nonblank reason');}
    $f->reject(fn()=>$s->record(['correction_of_transaction_id'=>$probeIn['transaction_id'],'notes'=>'Insufficient reversal'],$a,'review-insufficient'),409,'reversal cannot create negative balance');
    $s->record(['product_id'=>$probe,'type'=>'OUT','quantity'=>1],$a,'review-zero');
    $version=(int)$f->pdo->query("SELECT version FROM products WHERE product_id={$probe}")->fetchColumn();
    (new BizStock\Services\ProductService($f->pdo))->mutate($probe,'DELETE',['expected_version'=>$version],$a,'review');
    $f->reject(fn()=>$s->record(['correction_of_transaction_id'=>$probeIn['transaction_id'],'notes'=>'Archived reversal'],$a,'review-archived'),409,'archived reversal rejected');
    $raceProduct=$f->product('REVIEW-KEY');$conflict=$f->workers([['actor'=>2,'key'=>'review-conflict','body'=>['product_id'=>$raceProduct,'type'=>'IN','quantity'=>1]],['actor'=>2,'key'=>'review-conflict','body'=>['product_id'=>$raceProduct,'type'=>'IN','quantity'=>2]]]);
    $statuses=array_column($conflict,'status');sort($statuses);$f->check($statuses===[201,409]&&(int)$f->pdo->query("SELECT COUNT(*) FROM transactions WHERE product_id={$raceProduct}")->fetchColumn()===1,'concurrent changed payload key conflicts with one effect');
    [$status]=$f->http('/api/v1/transactions/'.$probeIn['transaction_id'],$jar,'DELETE',[],$jar['token']);$f->check($status===405,'direct history DELETE unavailable');
    [$status]=$f->http('/api/v1/transactions',$jar,'POST',['correction_of_transaction_id'=>$probeIn['transaction_id'],'notes'=>'Forbidden'],$jar['token'],'review-http-reversal');$f->check($status===403,'direct Staff reversal forbidden');
    $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'review probes reconcile after complete rollbacks');
    echo 'PASS: '.$f->checks.' Stage 8 actual database assertions ('.$f->pdo->query('SELECT VERSION()')->fetchColumn().').'.PHP_EOL;
}finally{$f->close();}
