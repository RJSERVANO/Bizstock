<?php
declare(strict_types=1);

use BizStock\Support\{Config,PdoFactory,MigrationRunner,PasswordPolicy,StockReconciliation,DatabasePlatform};
use BizStock\Services\{AdminBootstrap,DevelopmentSeeder};

$config=require dirname(__DIR__,2).'/config/bootstrap.php';
if(!in_array($config->environment,['local','test'],true) || !in_array($config->dbHost,['127.0.0.1','localhost','::1'],true)){throw new RuntimeException('Stage 4 integration requires disposable local/test databases.');}
$adminUser=getenv('TEST_DB_ADMIN_USER'); $adminPassword=getenv('TEST_DB_ADMIN_PASSWORD');
if($adminUser===false && $config->dbTarget==='mariadb104-local'){
    $file=$config->root.'/.tools/mariadb/credentials.json';
    if(!is_file($file)){throw new RuntimeException('Private test database administrator credentials required.');}
    $adminUser='root';$adminPassword=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR)['root_password'];
}
if($adminUser===false || $adminPassword===false){throw new RuntimeException('Provide private TEST_DB_ADMIN_USER and TEST_DB_ADMIN_PASSWORD for disposable database creation.');}
$makeConfig=static fn(string $database):Config=>new Config($config->root,'test',$config->basePath,$config->currency,$config->timezone,$config->dbHost,$config->dbPort,$database,$adminUser,$adminPassword,$config->logPath,$config->dbTarget);
$admin=(new PdoFactory($makeConfig($config->dbName)))->connection();
$databases=[];$checks=[];
$check=static function(bool $ok,string $label) use(&$checks):void{if(!$ok){throw new RuntimeException('FAIL: '.$label);} $checks[]=$label;};
$reject=static function(callable $action,string $code,string $label) use($check):void{
    try{$action();}catch(PDOException $error){$check(DatabasePlatform::failureCode($error)===$code,$label);return;}
    throw new RuntimeException('FAIL: accepted '.$label);
};
$refuse=static function(callable $action,string $label) use($check):void{
    try{$action();}catch(RuntimeException|InvalidArgumentException){$check(true,$label);return;}throw new RuntimeException('FAIL: accepted '.$label);
};
$sql=static function(PDO $pdo,string $query,array $parameters=[]):PDOStatement{$statement=$pdo->prepare($query);$statement->execute($parameters);return $statement;};
$fresh=static function() use(&$databases,$admin,$makeConfig):array{
    $name='bizstock_stage4_'.bin2hex(random_bytes(6));
    $admin->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$databases[]=$name;
    $testConfig=$makeConfig($name);return [(new PdoFactory($testConfig))->connection(),$testConfig];
};
$snapshot=static function(PDO $pdo):string{
    $data=[];foreach(['users','categories','suppliers','products','transactions','operation_requests','audit_events','rate_limits','security_locks','schema_migrations'] as $table){$data[$table]=$pdo->query("SELECT * FROM `{$table}` ORDER BY 1")->fetchAll();}
    return hash('sha256',json_encode($data,JSON_THROW_ON_ERROR));
};
$cli=static function(Config $testConfig,string $script,array $arguments=[],string $input='',array $overrides=[]):array{
    $environment=array_replace(getenv(),['APP_ENV'=>'test','DB_TARGET'=>$testConfig->dbTarget,'DB_HOST'=>$testConfig->dbHost,'DB_PORT'=>(string)$testConfig->dbPort,'DB_NAME'=>$testConfig->dbName,'DB_USER'=>$testConfig->dbUser,'DB_PASSWORD'=>$testConfig->dbPassword],$overrides);
    $process=proc_open([PHP_BINARY,$testConfig->root.'/bin/'.$script,...$arguments],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$testConfig->root,$environment);
    if(!is_resource($process)){throw new RuntimeException('Cannot start CLI verification worker.');}
    fwrite($pipes[0],$input);fclose($pipes[0]);$output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    return [proc_close($process),$output,$error];
};
try{
    [$pdo,$upgradeConfig]=$fresh();$runner=new MigrationRunner($pdo,$config->root.'/database/migrations');
    $check(count($runner->run('001_domain.sql'))===1,'fresh first migration applied');
    $password=bin2hex(random_bytes(16));$hash=PasswordPolicy::hash($password);
    $sql($pdo,"INSERT INTO users(full_name,username,password_hash,role) VALUES ('Upgrade account','upgrade.staff',?,'Staff')",[$hash]);$actor=(int)$pdo->lastInsertId();
    $pdo->exec("INSERT INTO categories(category_name) VALUES ('Upgrade category')");$category=(int)$pdo->lastInsertId();
    $pdo->exec("INSERT INTO suppliers(supplier_name) VALUES ('Upgrade supplier')");$supplier=(int)$pdo->lastInsertId();
    $sql($pdo,"INSERT INTO products(sku,product_name,category_id,supplier_id,price,quantity) VALUES ('UPGRADE-1','Existing product',?,?,'245.00',3)",[$category,$supplier]);$product=(int)$pdo->lastInsertId();
    $fingerprint=hash('sha256','fixture');
    $sql($pdo,"INSERT INTO transactions(product_id,user_id,type,quantity,unit_price,request_key,payload_hash) VALUES (?,?,'IN',3,'245.00','upgrade-opening',?)",[$product,$actor,$fingerprint]);$original=(int)$pdo->lastInsertId();
    $before=[];foreach(['users','categories','suppliers','products','transactions'] as $table){$before[$table]=$pdo->query("SELECT * FROM `{$table}` ORDER BY 1")->fetchAll();}
    $result=$runner->run();$check($result['002_support.sql']==='applied' && $result['003_access_indexes.sql']==='applied','additive upgrade applied');
    foreach($before as $table=>$rows){$after=$pdo->query("SELECT * FROM `{$table}` ORDER BY 1")->fetchAll();foreach($after as &$r){unset($r['profile_image'],$r['product_image']);}unset($r);$check($rows===$after,'upgrade preserved '.$table);}
    $state=$snapshot($pdo);$check(count(array_filter($runner->run(),static fn(string $status):bool=>$status==='already applied'))===4 && $snapshot($pdo)===$state,'safe rerun preserves all rows/checksums/timestamps');
    $check((int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()===10,'five domain and five supporting tables');
    $check((int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND ENGINE<>'InnoDB'")->fetchColumn()===0,'all tables InnoDB');
    $check((int)$pdo->query('SELECT COUNT(*) FROM security_locks WHERE lock_id=1')->fetchColumn()===1,'single security sentinel');
    $reject(fn()=>$pdo->exec('INSERT INTO security_locks VALUES (2)'),'constraint','extra security sentinel rejected');
    $refuse(fn()=>(new DevelopmentSeeder($pdo,$upgradeConfig))->run(),'seeder refuses existing installation');
    $refuse(fn()=>(new AdminBootstrap($pdo))->create('owner','Owner',$password),'bootstrap cannot replace existing account');
    $check($snapshot($pdo)===$state,'refused commands preserve existing data');

    foreach([
        ["UPDATE users SET role='Owner'",'constraint','invalid role'],["UPDATE users SET role='admin'",'constraint','case-sensitive role'],
        ["UPDATE users SET username='Upper'",'constraint','canonical username'],["UPDATE users SET session_version=0",'constraint','positive session version'],
        ["UPDATE users SET is_active=2",'constraint','boolean active'],["UPDATE users SET must_change_password=2",'constraint','boolean forced reset'],
        ["UPDATE products SET sku='lower'",'constraint','canonical SKU'],["UPDATE products SET sku=' BAD '",'constraint','SKU whitespace'],
        ["UPDATE products SET price=-0.01",'constraint','negative product price'],["UPDATE products SET quantity=-1",'constraint','negative stock'],
        ["UPDATE products SET minimum_threshold=-1",'constraint','negative threshold'],["UPDATE products SET version=0",'constraint','positive version'],
        ["UPDATE products SET category_id=999999",'reference','required category reference'],["UPDATE products SET supplier_id=999999",'reference','required supplier reference'],
        ["UPDATE products SET category_id=NULL",'database','nonnull category'],["UPDATE products SET quantity=2147483648",'range','integer overflow'],
        ["UPDATE products SET price=100000000.00",'range','decimal overflow'],["UPDATE transactions SET type='BAD'",'constraint','valid movement type'],
        ["UPDATE transactions SET type='in'",'constraint','case-sensitive movement type'],["UPDATE transactions SET quantity=0",'constraint','positive movement quantity'],
        ["UPDATE transactions SET unit_price=-1",'constraint','nonnegative historical price'],["UPDATE transactions SET product_id=999999",'reference','movement product reference'],
        ["UPDATE transactions SET user_id=999999",'reference','movement actor reference'],["UPDATE transactions SET correction_of_transaction_id=999999",'reference','correction reference'],
        ["UPDATE transactions SET payload_hash='invalid'",'constraint','SHA256 fingerprint'],["UPDATE transactions SET request_key=' '",'constraint','nonempty request key'],
        ["DELETE FROM categories",'reference','category deletion restricted'],["DELETE FROM suppliers",'reference','supplier deletion restricted'],
        ["DELETE FROM products",'reference','product history preserved'],["DELETE FROM users",'reference','actor history preserved'],
        ["INSERT INTO categories(category_name) VALUES ('UPGRADE CATEGORY')",'duplicate','category case uniqueness'],
        ["INSERT INTO categories(category_name) VALUES (' whitespace ')",'constraint','trimmed category name'],
    ] as [$query,$code,$label]){$reject(fn()=>$pdo->exec($query),$code,$label);}
    $reject(fn()=>$sql($pdo,"INSERT INTO users(full_name,username,password_hash,role) VALUES ('Duplicate','upgrade.staff',?,'Staff')",[$hash]),'duplicate','unique usernames');
    $reject(fn()=>$sql($pdo,"INSERT INTO products(sku,product_name,category_id,supplier_id,price) VALUES ('UPGRADE-1','Duplicate',?,?,0)",[$category,$supplier]),'duplicate','unique SKU');
    $reject(fn()=>$sql($pdo,"INSERT INTO transactions(product_id,user_id,type,quantity,unit_price,request_key,payload_hash) VALUES (?,?,'IN',1,0,'upgrade-opening',?)",[$product,$actor,$fingerprint]),'duplicate','actor-scoped movement uniqueness');
    $sql($pdo,"INSERT INTO transactions(product_id,user_id,type,quantity,unit_price,request_key,payload_hash,correction_of_transaction_id,notes) VALUES (?,?,'OUT',3,'245.00','reversal',?,?,'Full correction fixture')",[$product,$actor,$fingerprint,$original]);$reversal=(int)$pdo->lastInsertId();
    $reject(fn()=>$sql($pdo,"INSERT INTO transactions(product_id,user_id,type,quantity,unit_price,request_key,payload_hash,correction_of_transaction_id) VALUES (?,?,'OUT',3,0,'duplicate-reversal',?,?)",[$product,$actor,$fingerprint,$original]),'duplicate','unique reversal link');
    $reject(fn()=>$sql($pdo,'DELETE FROM transactions WHERE transaction_id=?',[$original]),'reference','referenced original preserved');
    $sql($pdo,'DELETE FROM transactions WHERE transaction_id=?',[$reversal]); // Disposable fixture cleanup only; application never provides this path.
    $check((int)$pdo->query('SELECT COUNT(*) FROM transactions WHERE correction_of_transaction_id IS NULL')->fetchColumn()===1,'ordinary movement NULL correction allowed');
    $sql($pdo,"INSERT INTO operation_requests(actor_id,operation_kind,request_key,payload_hash,response_status,response_json) VALUES (?,'product.create','operation-1',?,201,'{\"price\":\"245.00\"}')",[$actor,$fingerprint]);
    $reject(fn()=>$sql($pdo,"INSERT INTO operation_requests(actor_id,operation_kind,request_key,payload_hash) VALUES (?,'movement','operation-1',?)",[$actor,$fingerprint]),'duplicate','cross-kind actor-scoped operation uniqueness');
    $reject(fn()=>$pdo->exec("UPDATE operation_requests SET response_json='invalid'"),'constraint','replay JSON validation');
    $reject(fn()=>$pdo->exec('UPDATE operation_requests SET response_status=500'),'constraint','success replay status validation');
    $reject(fn()=>$pdo->exec('UPDATE operation_requests SET response_json=NULL'),'constraint','complete replay shape');
    $reject(fn()=>$pdo->exec('UPDATE operation_requests SET actor_id=999999'),'reference','operation actor reference');
    $sql($pdo,"INSERT INTO audit_events(actor_id,action,entity_type,entity_id,after_json,request_id) VALUES (?,'test','product',?,'{}','fixture-request')",[$actor,$product]);
    $reject(fn()=>$pdo->exec("UPDATE audit_events SET after_json='invalid'"),'constraint','audit JSON validation');
    $reject(fn()=>$pdo->exec("UPDATE audit_events SET before_json='invalid'"),'constraint','audit before JSON validation');
    $reject(fn()=>$pdo->exec('UPDATE audit_events SET actor_id=999999'),'reference','audit actor reference');
    $sql($pdo,"INSERT INTO rate_limits(limiter_key,window_started_at,reset_at,attempts) VALUES (?,'2026-10-11 00:00:00','2026-10-11 00:01:00',0)",[$fingerprint]);
    $reject(fn()=>$pdo->exec('UPDATE rate_limits SET attempts=-1'),'constraint','nonnegative limiter attempts');
    $reject(fn()=>$pdo->exec('UPDATE rate_limits SET reset_at=window_started_at'),'constraint','ordered limiter window');
    $reject(fn()=>$pdo->exec("UPDATE rate_limits SET limiter_key='plaintext'"),'constraint','hashed limiter identity');
    $check((string)$pdo->query('SELECT price FROM products')->fetchColumn()==='245.00','DECIMAL remains string');
    $reconciliation=new StockReconciliation($pdo);$check($reconciliation->inspect()['discrepancies']===[],'upgraded stock reconciles');
    $pdo->exec('UPDATE products SET quantity=4');$driftState=$snapshot($pdo);$drift=$reconciliation->inspect();
    $check(count($drift['discrepancies'])===1 && (string)$drift['discrepancies'][0]['movement_balance']==='3','drift detected');
    $check($snapshot($pdo)===$driftState,'reconciliation never repairs drift');$pdo->exec('UPDATE products SET quantity=3');
    $pdo->exec('UPDATE products SET quantity=4');$driftState=$snapshot($pdo);
    [$exit,$output]=$cli($upgradeConfig,'reconcile-stock.php');
    $check($exit===1 && count(json_decode($output,true,512,JSON_THROW_ON_ERROR)['discrepancies'])===1,'reconciliation CLI drift exit 1');
    $check($snapshot($pdo)===$driftState,'CLI drift check read-only');$pdo->exec('UPDATE products SET quantity=3');
    [$exit,$output]=$cli($upgradeConfig,'reconcile-stock.php');$check($exit===0 && json_decode($output,true)['discrepancies']===[],'reconciliation CLI clean exit 0');
    $sql($pdo,"INSERT INTO products(sku,product_name,category_id,supplier_id,price,is_active) VALUES ('NO-MOVEMENTS','Archived zero',?,?,0,0)",[$category,$supplier]);
    $check($reconciliation->inspect()['products_checked']===2 && $reconciliation->inspect()['discrepancies']===[],'archived/no-movement products included');
    $indexes=$pdo->query("SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    foreach(['idx_products_category_active','idx_products_supplier_active','idx_transactions_date','idx_transactions_product_date','idx_transactions_actor_date','idx_audit_date','idx_audit_entity','idx_rate_reset','idx_users_admin'] as $index){$check(in_array($index,$indexes,true),'access index '.$index);}
    $plan=$sql($pdo,'EXPLAIN SELECT * FROM transactions FORCE INDEX(idx_transactions_product_date) WHERE product_id=? ORDER BY transaction_date,transaction_id',[$product])->fetch();
    $check($plan['key']==='idx_transactions_product_date','history index usable in real EXPLAIN');
    // Refuse tampered/missing history before executing new DDL; never edit shipped migrations.
    $pdo->exec("UPDATE schema_migrations SET checksum=REPEAT('0',64) WHERE migration_id='001_domain.sql'");
    $refuse(fn()=>$runner->run(),'changed applied checksum refused');
    $sql($pdo,"UPDATE schema_migrations SET checksum=? WHERE migration_id='001_domain.sql'",[hash_file('sha256',$config->root.'/database/migrations/001_domain.sql')]);
    $pdo->exec("UPDATE schema_migrations SET status='running' WHERE migration_id='003_access_indexes.sql'");$refuse(fn()=>$runner->run(),'incomplete DDL refused');
    $pdo->exec("UPDATE schema_migrations SET status='applied' WHERE migration_id='003_access_indexes.sql'");

    [$bootstrap,$bootstrapConfig]=$fresh();(new MigrationRunner($bootstrap,$config->root.'/database/migrations'))->run();
    $service=new AdminBootstrap($bootstrap);
    $refuse(fn()=>$service->create('owner','Owner','short'),'weak password refused');
    $refuse(fn()=>$service->create('owner','Owner',str_repeat('a',73)),'bcrypt truncation prevented');
    $bootstrap->exec("CREATE TRIGGER stage4_audit_failure BEFORE INSERT ON audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Disposable audit failure'");
    $refuse(fn()=>$service->create('owner','Owner',$password),'audit failure aborts bootstrap');
    $check((int)$bootstrap->query('SELECT COUNT(*) FROM users')->fetchColumn()===0,'failed bootstrap leaves no account');
    $bootstrap->exec('DROP TRIGGER stage4_audit_failure');
    [$exit,$output,$error]=$cli($bootstrapConfig,'bootstrap-admin.php',['  Owner.Admin  ','Owner Example','--password-stdin'],$password."\n");
    $check($exit===0 && !str_contains($output.$error,$password),'bootstrap CLI private stdin, no secret output');
    $account=$bootstrap->query('SELECT * FROM users')->fetch();$id=(int)$account['user_id'];
    $check($account['username']==='owner.admin' && $account['role']==='Admin' && (int)$account['is_active']===1,'canonical active Admin created');
    $check(password_verify($password,$account['password_hash']) && $account['password_hash']!==$password,'bootstrap password hashed and verifiable');
    $check((int)$account['session_version']===1 && (int)$account['must_change_password']===1,'initial session/reset fields');
    $audit=$bootstrap->query('SELECT after_json FROM audit_events')->fetchColumn();$check(!str_contains($audit,$password) && !str_contains($audit,'password_hash'),'bootstrap audit excludes secrets');
    $bootstrapState=$snapshot($bootstrap);$refuse(fn()=>$service->create('second','Second',$password),'second bootstrap refused');$check($snapshot($bootstrap)===$bootstrapState,'bootstrap rerun preserves account/audit');
    [$exit,$output,$error]=$cli($bootstrapConfig,'bootstrap-admin.php',['second','Second','--password-stdin'],$password."\n");
    $check($exit===2 && !str_contains($output.$error,$password) && $snapshot($bootstrap)===$bootstrapState,'bootstrap CLI safe refusal');

    [$seed,$seedConfig]=$fresh();(new MigrationRunner($seed,$config->root.'/database/migrations'))->run();
    $seeder=new DevelopmentSeeder($seed,$seedConfig);$emptySeedState=$snapshot($seed);
    $refuse(fn()=>$seeder->run(static function(array $credentials):void{throw new RuntimeException('Disposable credential delivery failure');}),'credential persistence failure aborts seed');
    $check($snapshot($seed)===$emptySeedState,'failed seed leaves no accounts/products/movements/replays/audit');
    $seedResult=$seeder->run();
    $check($seedResult['status']==='seeded' && count($seedResult['credentials'])===3,'development fixture roles seeded');
    foreach($seedResult['credentials'] as $role=>$credential){$user=$sql($seed,'SELECT * FROM users WHERE username=?',[$credential['username']])->fetch();$check($user['role']===$role && password_verify($credential['password'],$user['password_hash']) && (int)$user['must_change_password']===1,'random hashed forced-change '.$role);}
    $check((new StockReconciliation($seed))->inspect()===['products_checked'=>3,'discrepancies'=>[]],'seed stock reconciles');
    $quantities=$seed->query('SELECT sku,quantity FROM products ORDER BY sku')->fetchAll(PDO::FETCH_KEY_PAIR);
    $check($quantities===['DEV-FOLDERS'=>0,'DEV-PAPER'=>17,'DEV-PENS'=>10],'zero/equality/populated seed balances');
    $check((int)$seed->query('SELECT COUNT(*) FROM transactions')->fetchColumn()===3 && (int)$seed->query('SELECT COUNT(*) FROM operation_requests')->fetchColumn()===3,'opening/OUT history and replay records consistent');
    $seedState=$snapshot($seed);$check($seeder->run()===['status'=>'already seeded','credentials'=>[]] && $snapshot($seed)===$seedState,'seed rerun cannot duplicate effects or rotate passwords');
    $production=new Config($config->root,'production','','PHP','Asia/Manila',$config->dbHost,$config->dbPort,$seedConfig->dbName,$adminUser,$adminPassword,$config->logPath,'mysql84');
    $refuse(fn()=>(new DevelopmentSeeder($seed,$production))->run(),'production seed refusal before mutation');
    $check($snapshot($seed)===$seedState,'production refusal preserves data');
    [$exit]=$cli($seedConfig,'seed-development.php',[],'',['APP_ENV'=>'production','DB_TARGET'=>'mysql84']);
    $check($exit===2 && $snapshot($seed)===$seedState,'production seed CLI rejected before connecting');
    $check((int)$seed->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE()')->fetchColumn()===0,'no quantity or other production triggers');
    [$untracked]=$fresh();$untracked->exec('CREATE TABLE users (user_id INT PRIMARY KEY) ENGINE=InnoDB');$untracked->exec('INSERT INTO users VALUES (42)');
    $refuse(fn()=>(new MigrationRunner($untracked,$config->root.'/database/migrations'))->run(),'unmanaged existing schema refused');
    $check((int)$untracked->query('SELECT user_id FROM users')->fetchColumn()===42 && (int)$untracked->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn()===0,'unmanaged data left intact');
    $report=['verified_at_utc'=>gmdate('c'),'engine'=>$config->dbTarget,'scope'=>'Stage 4 schema/upgrade/bootstrap/seeds/reconciliation only','assertions'=>count($checks),'passed'=>$checks,'mysql84'=>$config->dbTarget==='mysql84'?'Stage 4 verified by this run':'pending; no MySQL server tested'];
}finally{
    // Only names created in this invocation; never drop the configured database or existing data.
    foreach(array_reverse($databases) as $database){if(!preg_match('/^bizstock_stage4_[a-f0-9]{12}$/D',$database)){throw new RuntimeException('Unsafe disposable database name.');}$admin->exec("DROP DATABASE `{$database}`");}
}
file_put_contents($config->root.'/docs/stage4-results.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo 'PASS: '.count($checks).' Stage 4 assertions on '.$config->dbTarget.'; disposable databases removed. MySQL evidence remains independent.'.PHP_EOL;
