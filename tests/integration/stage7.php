<?php
declare(strict_types=1);

use BizStock\Support\{Config,PdoFactory,MigrationRunner,PasswordPolicy,SessionManager,StockReconciliation};
use BizStock\Http\Authorization;

$config=require dirname(__DIR__,2).'/config/bootstrap.php';
if(!in_array($config->environment,['local','test'],true) || !in_array($config->dbHost,['127.0.0.1','localhost','::1'],true)) { throw new RuntimeException('Disposable local/test database required.'); }
$adminUser=getenv('TEST_DB_ADMIN_USER'); $adminPassword=getenv('TEST_DB_ADMIN_PASSWORD');
if($adminUser===false && $config->dbTarget==='mariadb104-local') {
    $adminUser='root'; $adminPassword=json_decode(file_get_contents($config->root.'/.tools/mariadb/credentials.json'),true,512,JSON_THROW_ON_ERROR)['root_password'];
}
if($adminUser===false || $adminPassword===false) { throw new RuntimeException('Private test database administrator credentials required.'); }
$db='bizstock_stage7_'.bin2hex(random_bytes(6));
$make=static fn(string $name):Config=>new Config($config->root,'test','/bizstock','PHP','Asia/Manila',$config->dbHost,$config->dbPort,$name,$adminUser,$adminPassword,$config->logPath,$config->dbTarget);
$admin=(new PdoFactory($make($config->dbName)))->connection();
$checks=0; $servers=[]; $created=false;
$check=static function(bool $ok,string $label)use(&$checks):void { if(!$ok){throw new RuntimeException('FAIL: '.$label);} $checks++; };
$password=bin2hex(random_bytes(16)); $next=bin2hex(random_bytes(16));
$start=static function(array $overrides=[])use($config,$db,$adminUser,$adminPassword,&$servers):string {
    $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$error); $address=stream_socket_get_name($socket,false); fclose($socket);
    $port=substr($address,strrpos($address,':')+1);
    $environment=array_replace(getenv(),['APP_ENV'=>'test','APP_BASE_PATH'=>'/bizstock','DB_TARGET'=>$config->dbTarget,'DB_HOST'=>$config->dbHost,'DB_PORT'=>(string)$config->dbPort,'DB_NAME'=>$db,'DB_USER'=>$adminUser,'DB_PASSWORD'=>$adminPassword,'SESSION_IDLE_SECONDS'=>'1800','SESSION_ABSOLUTE_SECONDS'=>'28800'],$overrides);
    $process=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,'-t','public','bin/router.php'],[0=>['pipe','r'],1=>['file',$config->root.'/var/logs/stage7-server.log','a'],2=>['file',$config->root.'/var/logs/stage7-server.log','a']],$pipes,$config->root,$environment,['bypass_shell'=>true]);
    if(!is_resource($process)){throw new RuntimeException('Cannot start test server.');} fclose($pipes[0]); $servers[]=$process;
    for($i=0;$i<100;$i++) { $stream=@stream_socket_client('tcp://127.0.0.1:'.$port,$code,$error,0.1); if($stream){fclose($stream);return 'http://127.0.0.1:'.$port.'/bizstock';} usleep(50000); }
    throw new RuntimeException('Test server did not start.');
};
$request=static function(string $origin,string $path,array &$jar,string $method='GET',?array $body=null,?string $csrf=null):array {
    $headers=['Accept: application/json']; if($jar){$headers[]='Cookie: bizstock_session='.$jar['cookie'];} if($csrf!==null){$headers[]='X-CSRF-Token: '.$csrf;}
    if($body!==null){$headers[]='Content-Type: application/json';}
    $context=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$body===null?'':json_encode($body===[]?(object)[]:$body,JSON_THROW_ON_ERROR),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15]]);
    $text=file_get_contents($origin.$path,false,$context); $responseHeaders=http_get_last_response_headers();
    preg_match('/ (\d{3}) /',$responseHeaders[0],$match);
    foreach($responseHeaders as $header){if(preg_match('/^Set-Cookie: bizstock_session=([^;]*)/i',$header,$cookie)){$jar['cookie']=$cookie[1];}}
    $json=json_decode($text,true); return [(int)$match[1],$json,$responseHeaders,$text];
};
$csrf=static function(string $origin,array &$jar)use($request,$check):string {
    [$status,$json,$headers]=$request($origin,'/api/v1/auth/csrf',$jar);
    $check($status===200 && preg_match('/^[a-f0-9]{64}$/D',$json['data']['csrf_token'])===1,'real CSRF bootstrap');
    return $json['data']['csrf_token'];
};
$login=static function(string $origin,array &$jar,string $username,string $secret)use($csrf,$request,$check):array {
    $token=$csrf($origin,$jar); $old=$jar['cookie'];
    [$status,$json,$headers]=$request($origin,'/api/v1/auth/login',$jar,'POST',['username'=>$username,'password'=>$secret],$token);
    $check($status===200,'real login '.$username); $check($jar['cookie']!==$old,'login rotates session ID');
    $check(!isset($json['data']['password_hash'],$json['data']['session_version']),'identity excludes secrets'); return $json['data'];
};
try {
    $admin->exec("CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $created=true;
    $pdo=(new PdoFactory($make($db)))->connection(); (new MigrationRunner($pdo,$config->root.'/database/migrations'))->run();
    $insert=$pdo->prepare('INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,?,0)');
    foreach(['admin'=>'Admin','staff'=>'Staff','viewer'=>'Viewer'] as $name=>$role){$insert->execute([$name,$name,PasswordPolicy::hash($password),$role]);}
    $origin=$start(); $jars=[]; $tokens=[];
    foreach(['admin','staff','viewer'] as $name){$jars[$name]=[];$login($origin,$jars[$name],$name,$password);$tokens[$name]=$csrf($origin,$jars[$name]);}
    $anon=[];
    foreach(['categories','suppliers'] as $resource) {
        [$status]=$request($origin,'/api/v1/'.$resource,$anon); $check($status===401,'anonymous lookup denied');
        foreach(['staff','viewer'] as $role) {
            [$status,$json]=$request($origin,'/api/v1/'.$resource,$jars[$role]);$check($status===($role==='viewer'&&$resource==='suppliers'?403:200),'lookup read role '.$resource.' '.$role);
            foreach(['POST','PATCH','DELETE'] as $method) {
                [$status]=$request($origin,'/api/v1/'.$resource.($method==='POST'?'':'/999'),$jars[$role],$method,[],$tokens[$role]);$check($status===403,'forbidden direct write '.$resource.' '.$method.' '.$role);
            }
        }
        [$status]=$request($origin,'/api/v1/'.$resource,$jars['admin'],'POST',[],'invalid');$check($status===403,'lookup CSRF enforced');
    }
    [$status,$json]=$request($origin,'/api/v1/categories',$jars['admin'],'POST',['category_name'=>'  Office supplies  '],$tokens['admin']);
    $category=$json['data']['category_id'];$check($status===201 && $json['data']['category_name']==='Office supplies','create/trim category');
    foreach(['office SUPPLIES','Office supplies '] as $duplicate){[$status,$json]=$request($origin,'/api/v1/categories',$jars['admin'],'POST',['category_name'=>$duplicate],$tokens['admin']);$check($status===409 && $json['error']['code']==='DUPLICATE_CATEGORY','category uniqueness');}
    foreach(['','   ',str_repeat('x',101),"bad\nname"] as $name){[$status]=$request($origin,'/api/v1/categories',$jars['admin'],'POST',['category_name'=>$name],$tokens['admin']);$check($status===422,'invalid category name');}
    [$status]=$request($origin,'/api/v1/categories',$jars['admin'],'POST',['category_name'=>'Unknown','price'=>'2.00'],$tokens['admin']);$check($status===422,'unknown lookup body field');
    [$status,$json]=$request($origin,'/api/v1/suppliers',$jars['admin'],'POST',['supplier_name'=>'Supplier One','contact_email'=>' sales@example.com '],$tokens['admin']);
    $supplier=$json['data']['supplier_id'];$check($status===201 && $json['data']['contact_email']==='sales@example.com' && str_ends_with($json['data']['created_at'],'Z'),'supplier create/email/timestamp');
    foreach(['not-an-email',"one@example.com\n",str_repeat('x',101).'@example.com'] as $email){[$status]=$request($origin,'/api/v1/suppliers',$jars['admin'],'POST',['supplier_name'=>'Bad email','contact_email'=>$email],$tokens['admin']);$check($status===422,'invalid supplier email');}
    [$status]=$request($origin,'/api/v1/suppliers',$jars['admin'],'POST',['supplier_name'=>str_repeat('x',151)],$tokens['admin']);$check($status===422,'supplier name bound');
    [$status,$json]=$request($origin,'/api/v1/suppliers/'.$supplier,$jars['admin'],'PATCH',['supplier_name'=>'Renamed Supplier'],$tokens['admin']);$check($status===200 && $json['data']['contact_email']==='sales@example.com','partial update retains omitted email');
    [$status,$json]=$request($origin,'/api/v1/suppliers/'.$supplier,$jars['admin'],'PATCH',['contact_email'=>''],$tokens['admin']);$check($status===200 && $json['data']['contact_email']===null,'blank email clears');
    [$status]=$request($origin,'/api/v1/suppliers/'.$supplier,$jars['admin'],'PATCH',[],$tokens['admin']);$check($status===422,'empty patch rejected');
    [$status,$json]=$request($origin,'/api/v1/categories/'.$category,$jars['admin'],'PATCH',['category_name'=>'Edited Office'],$tokens['admin']);$check($status===200 && $json['data']['category_name']==='Edited Office','edit category');
    [$status,$json]=$request($origin,'/api/v1/categories?q=Edited',$jars['viewer']);$check($status===200 && count($json['data'])===1 && array_keys($json['data'][0])===['category_id','category_name'],'Viewer positive category fields');
    [$status,$json]=$request($origin,'/api/v1/suppliers?q=Renamed',$jars['staff']);$check($status===200 && count($json['data'])===1 && $json['data'][0]['supplier_name']==='Renamed Supplier','Staff supplier read');
    foreach(['page=0','per_page=101','sort=price','direction=bad','q='.str_repeat('x',101),'supplier_id=1'] as $query){[$status]=$request($origin,'/api/v1/categories?'.$query,$jars['admin']);$check($status===422,'query allowlist/bounds');}
    [$status,$json]=$request($origin,'/api/v1/suppliers?q=private&sort=contact_email',$jars['viewer']);$check($status===403 && $json['error']['message']==='Access denied.','Viewer forbidden filter does not leak details');
    $bulk=$pdo->prepare('INSERT INTO categories(category_name) VALUES (?)'); foreach(range(1,30) as $n){$bulk->execute(['Page '.str_pad((string)$n,2,'0',STR_PAD_LEFT)]);}
    [$status,$first]=$request($origin,'/api/v1/categories?q=Page&per_page=10&sort=category_name',$jars['admin']);
    [$status,$second]=$request($origin,'/api/v1/categories?q=Page&per_page=10&sort=category_name&page=2',$jars['admin']);
    $check(count($first['data'])===10 && $first['meta']['total']===30 && $first['meta']['total_pages']===3 && $second['data'][0]['category_name']==='Page 11','real search pagination stable');
    foreach(['100% literal','under_score','normal'] as $name){$bulk->execute([$name]);}
    [$status,$json]=$request($origin,'/api/v1/categories?q=%25',$jars['admin']);$check(count($json['data'])===1 && $json['data'][0]['category_name']==='100% literal','LIKE percent literal');
    [$status,$json]=$request($origin,'/api/v1/categories?q=%27%20OR%201%3D1%20--',$jars['admin']);$check($status===200 && !$json['data'],'search injection harmless');
    [$status,$json]=$request($origin,'/api/v1/categories',$jars['admin'],'POST',['category_name'=>'100% literal'],$tokens['admin']);$check($status===409,'database inserted duplicate also refused');
    [$status,$json]=$request($origin,'/api/v1/categories/'.$category,$jars['admin'],'PATCH',['category_name'=>'100% literal'],$tokens['admin']);$check($status===409 && $json['error']['code']==='DUPLICATE_CATEGORY','duplicate edit also rolls back');
    $product=$pdo->prepare("INSERT INTO products(sku,product_name,category_id,supplier_id,price,quantity,is_active) VALUES ('REF','Referenced',?,?,'1.00',0,0)");$product->execute([$category,$supplier]);
    $auditBefore=(int)$pdo->query('SELECT COUNT(*) FROM audit_events')->fetchColumn();
    foreach(['categories/'.$category,'suppliers/'.$supplier] as $route){[$status,$json]=$request($origin,'/api/v1/'.$route,$jars['admin'],'DELETE',null,$tokens['admin']);$check($status===409 && $json['error']['code']==='REFERENCED_RECORD' && str_contains($json['error']['message'],'Reassign'),'archived reference deletion blocked');}
    $check((int)$pdo->query('SELECT COUNT(*) FROM audit_events')->fetchColumn()===$auditBefore && (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()===1,'failed deletions leave audits and products unchanged');
    $pdo->exec('UPDATE products SET is_active=1');
    foreach(['categories/'.$category,'suppliers/'.$supplier] as $route){[$status]=$request($origin,'/api/v1/'.$route,$jars['admin'],'DELETE',null,$tokens['admin']);$check($status===409,'active reference deletion blocked');}
    $pdo->exec('UPDATE products SET is_active=0');
    foreach(['categories','suppliers'] as $resource){[$status]=$request($origin,'/api/v1/'.$resource.'/2147483647',$jars['admin'],'DELETE',null,$tokens['admin']);$check($status===404,'missing deletion 404');}
    $pdo->exec("CREATE TRIGGER fail_lookup_audit BEFORE INSERT ON audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='audit failure'");
    foreach([['categories','POST',null,['category_name'=>'Rolled back']],['categories','PATCH',$category,['category_name'=>'Must not change']],['categories','DELETE',(int)$first['data'][0]['category_id'],null],['suppliers','PATCH',$supplier,['supplier_name'=>'Must not change']]] as [$resource,$method,$id,$body]) {
        [$status,$json]=$request($origin,'/api/v1/'.$resource.($id?'/'.$id:''),$jars['admin'],$method,$body,$tokens['admin']);$check($status===500 && $json['error']['code']==='INTERNAL_ERROR','audit failure rejects mutation');
    }
    $pdo->exec('DROP TRIGGER fail_lookup_audit');
    $check($pdo->query("SELECT COUNT(*) FROM categories WHERE category_name='Rolled back'")->fetchColumn()==0 && $pdo->query("SELECT category_name FROM categories WHERE category_id={$category}")->fetchColumn()==='Edited Office' && $pdo->query("SELECT COUNT(*) FROM categories WHERE category_name='Page 01'")->fetchColumn()==1,'create/edit/delete audit rollback');
    $check($pdo->query("SELECT supplier_name FROM suppliers WHERE supplier_id={$supplier}")->fetchColumn()==='Renamed Supplier','supplier audit rollback');
    [$status,$json]=$request($origin,'/api/v1/categories/'.$second['data'][0]['category_id'],$jars['admin'],'DELETE',null,$tokens['admin']);$check($status===200 && $json['data']['deleted'],'unreferenced delete');
    $audit=$pdo->query('SELECT * FROM audit_events ORDER BY event_id')->fetchAll();
    $check(count($audit)===6 && $audit[0]['action']==='category.created' && $audit[0]['actor_id']===1 && json_decode($audit[0]['after_json'],true)['category_name']==='Office supplies','audit actor/action/values');
    $check($audit[0]['before_json']===null && $audit[array_key_last($audit)]['after_json']===null && preg_match('/^[a-f0-9]{32}$/',$audit[0]['request_id'])===1,'audit before/after/request ID');
    // Real browser fixture uses these existing DB rows; all writes remain disposable.
    $environment=array_replace(getenv(),['STAGE7_ORIGIN'=>$origin,'STAGE7_PASSWORD'=>$password]);
    $browser=proc_open(['node','tests/browser/lookups.cjs'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$config->root,$environment,['bypass_shell'=>true]);
    fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($browser);
    if($exit!==0){throw new RuntimeException('Browser acceptance failed: '.$err);}echo $out;$check(true,'browser lookups');
    $check((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()===1 && (int)$pdo->query('SELECT quantity FROM products')->fetchColumn()===0 && (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn()===0,'no stock/history side effects');
    echo 'PASS: '.$checks.' Stage 7 assertions on '.$pdo->query('SELECT VERSION()')->fetchColumn().'; MySQL and production HTTPS pending.'.PHP_EOL;
} finally {
    foreach($servers as $server){proc_terminate($server);proc_close($server);}
    if($created){$admin->exec("DROP DATABASE `{$db}`");}
    $directory=$config->root.'/var/sessions/'.hash('sha256','test|'.$db.'|/bizstock');
    if(is_dir($directory)){foreach(glob($directory.'/sess_*') as $file){unlink($file);}rmdir($directory);}
}
