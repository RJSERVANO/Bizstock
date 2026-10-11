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
$db='bizstock_stage6_'.bin2hex(random_bytes(6));
$make=static fn(string $name):Config=>new Config($config->root,'test','/bizstock','PHP','Asia/Manila',$config->dbHost,$config->dbPort,$name,$adminUser,$adminPassword,$config->logPath,$config->dbTarget);
$admin=(new PdoFactory($make($config->dbName)))->connection();
$checks=0; $servers=[]; $created=false;
$check=static function(bool $ok,string $label)use(&$checks):void { if(!$ok){throw new RuntimeException('FAIL: '.$label);} $checks++; };
$password=bin2hex(random_bytes(16)); $next=bin2hex(random_bytes(16));
$start=static function(array $overrides=[])use($config,$db,$adminUser,$adminPassword,&$servers):string {
    $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$error); $address=stream_socket_get_name($socket,false); fclose($socket);
    $port=substr($address,strrpos($address,':')+1);
    $environment=array_replace(getenv(),['APP_ENV'=>'test','APP_BASE_PATH'=>'/bizstock','DB_TARGET'=>$config->dbTarget,'DB_HOST'=>$config->dbHost,'DB_PORT'=>(string)$config->dbPort,'DB_NAME'=>$db,'DB_USER'=>$adminUser,'DB_PASSWORD'=>$adminPassword,'SESSION_IDLE_SECONDS'=>'1800','SESSION_ABSOLUTE_SECONDS'=>'28800'],$overrides);
    $process=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,'-t','public','bin/router.php'],[0=>['pipe','r'],1=>['file',$config->root.'/var/logs/stage6-server.log','a'],2=>['file',$config->root.'/var/logs/stage6-server.log','a']],$pipes,$config->root,$environment,['bypass_shell'=>true]);
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
    $insert=$pdo->prepare('INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,?,?)');
    foreach(['admin'=>'Admin','staff'=>'Staff','viewer'=>'Viewer','forced'=>'Staff','browser'=>'Viewer'] as $name=>$role) {
        $insert->execute([$name==='browser'?'<script>bad()</script> Browser':$name,$name,PasswordPolicy::hash($password),$role,in_array($name,['forced','browser'],true)?1:0]);
    }
    $origin=$start(); $anon=[];
    [$status]=$request($origin,'/account',$anon); $check($status===303,'account requires actual session');
    [$status]=$request($origin,'/api/v1/auth/me',$anon); $check($status===401,'anonymous me denied');
    foreach(Authorization::plannedRoutes() as $route=>$methods) {
        foreach($methods as $method=>$roles) { [$status]=$request($origin,str_replace('{id}','1',$route),$anon,$method,$method==='GET'?null:[]); $check($status===401,'anonymous matrix '.$method.' '.$route); }
    }
    [$status]=$request($origin,'/api/v1/auth/login',$anon,'POST',['username'=>'admin','password'=>$password]); $check($status===403,'login without CSRF denied');
    $token=$csrf($origin,$anon); $anonymousCookie=$anon;
    [$status]=$request($origin,'/api/v1/auth/login',$anon,'POST',['username'=>"\0admin\0",'password'=>$password],$token); $check($status===422,'shared username validation rejects controls before normalization');
    [$status,$json,$headers]=$request($origin,'/api/v1/auth/login',$anon,'POST',['username'=>'admin','password'=>'incorrect'],$token);
    $check($status===401 && $json['error']['code']==='INVALID_CREDENTIALS','wrong password generic'); $wrong=$json['error']['message'];
    [$status,$json]=$request($origin,'/api/v1/auth/login',$anon,'POST',['username'=>'missing','password'=>'incorrect'],$token);
    $check($status===401 && $json['error']['message']===$wrong,'unknown user equally generic');
    $fix=['cookie'=>str_repeat('a',32)]; $csrf($origin,$fix); $check($fix['cookie']!==str_repeat('a',32),'strict mode rejects attacker session ID');
    $jars=[];
    foreach(['admin'=>'Admin','staff'=>'Staff','viewer'=>'Viewer'] as $name=>$role) {
        $jar=[]; $identity=$login($origin,$jar,strtoupper($name),$password); $check($identity['role']===$role,'role from database');
        [$status,$json,$headers]=$request($origin,'/api/v1/auth/me',$jar);
        $check($status===200 && $json['data']['role']===$role,'me '.$role);
        $check(in_array('Cache-Control: no-store',$headers,true),'authenticated no-store');
        $writeToken=$csrf($origin,$jar);
        foreach(Authorization::plannedRoutes() as $route=>$methods) {
            foreach($methods as $method=>$roles) {
                $path=str_replace('{id}','1',$route); $write=$method!=='GET';
                if(str_starts_with($route,'/api/v1/users/{id}'))$path=str_replace('{id}','999999',$route);
                [$status,$json]=$request($origin,$path,$jar,$method,$write?[]:null,$write?$writeToken:null);
                $lookup=preg_match('~^/api/v1/(categories|suppliers)(?:/\{id\})?$~D',$route)===1;
                $allowed=$lookup?match($method){'GET'=>200,'POST','PATCH'=>422,default=>404}:404;
                if($route==='/api/v1/products'){$allowed=$method==='GET'?200:422;}
                if($route==='/api/v1/products/{id}'){$allowed=$method==='GET'?404:422;}
                if($route==='/api/v1/transactions'){$allowed=$method==='GET'?200:422;}
                if($route==='/api/v1/dashboard'||str_starts_with($route,'/api/v1/reports/')){$allowed=200;}
                if($route==='/api/v1/users'||$route==='/api/v1/audit-events')$allowed=$method==='GET'?200:422;
                if($route==='/api/v1/users/{id}')$allowed=$method==='DELETE'?404:422;
                if($route==='/api/v1/users/{id}/password-reset')$allowed=422;
                $check($status===(in_array($role,$roles,true)?$allowed:403),'matrix '.$role.' '.$method.' '.$route);
                $check(!str_contains(json_encode($json),'password_hash'),'no field leakage in denied/planned response');
                if($write && in_array($role,$roles,true)) { [$status]=$request($origin,$path,$jar,$method,[],'wrong'); $check($status===403,'CSRF future permitted mutation'); }
            }
        }
        $jars[$name]=$jar;
    }
    foreach(['/api/v1/products?sort=price','/api/v1/products?supplier_id=1'] as $path) {
        [$status,$json]=$request($origin,$path,$jars['viewer']); $check($status===403 && $json['error']['message']==='Access denied.','Viewer sensitive query generic refusal');
    }
    [$status]=$request($origin,'/api/v1/transactions',$jars['staff'],'POST',['correction_of_transaction_id'=>1],$csrf($origin,$jars['staff'])); $check($status===403,'Staff correction denied');
    $row=['product_id'=>1,'sku'=>'TEST','product_name'=>'Test','category_id'=>1,'category_name'=>'Test','quantity'=>0,'minimum_threshold'=>1,'stock_status'=>'out_of_stock','is_active'=>true,'price'=>'1.00','supplier_id'=>1,'supplier_name'=>'Secret','location'=>'Private','description'=>'Private','nested'=>['password_hash'=>'never']];
    $visible=Authorization::product($row,'Viewer'); $check(count($visible)===10 && $visible['has_image']===false && !isset($visible['price'],$visible['supplier_id'],$visible['nested']),'positive Viewer product projection drops nested extras');
    $check(Authorization::category(['category_id'=>1,'category_name'=>'Name','secret'=>'hidden'])===['category_id'=>1,'category_name'=>'Name'],'positive category projection');
    $forced=[]; $login($origin,$forced,'forced',$password); $other=[]; $login($origin,$other,'forced',$password);
    [$status,$json]=$request($origin,'/api/v1/auth/me',$forced); $check($status===403 && $json['error']['code']==='PASSWORD_CHANGE_REQUIRED','forced session restricted');
    [$status]=$request($origin,'/api/v1/products',$forced); $check($status===403,'forced business access denied');
    $forcedLogout=[]; $login($origin,$forcedLogout,'forced',$password);
    [$status]=$request($origin,'/api/v1/auth/logout',$forcedLogout,'POST',null,$csrf($origin,$forcedLogout)); $check($status===200,'forced session may logout');
    $token=$csrf($origin,$forced); $oldForced=$forced;
    [$status]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>'incorrect','new_password'=>$next],$token); $check($status===403,'current password verified');
    [$status]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>$password,'new_password'=>'short'],$token); $check($status===422,'password policy enforced');
    [$status,$json]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>$password,'new_password'=>$next],$token);
    $check($status===200 && !$json['data']['must_change_password'],'real forced change clears restriction'); $check($forced['cookie']!==$oldForced['cookie'],'password change rotates cookie');
    [$status]=$request($origin,'/api/v1/auth/me',$other); $check($status===401,'password change invalidates other sessions');
    [$status]=$request($origin,'/api/v1/auth/me',$forced); $check($status===200,'current session retained');
    [$status]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>$next,'new_password'=>$password],$token); $check($status===403,'old CSRF revoked');
    $dbUser=$pdo->query("SELECT * FROM users WHERE username='forced'")->fetch(); $check(password_verify($next,$dbUser['password_hash']) && (int)$dbUser['session_version']===2,'password persisted and version incremented');
    $audit=$pdo->query("SELECT before_json,after_json FROM audit_events WHERE action='account.password_changed'")->fetchAll();
    $check(count($audit)===1 && !str_contains(json_encode($audit),$next) && !str_contains(json_encode($audit),'password_hash'),'one secret-free password audit');
    // An audit failure must roll back the hash/version as well as the forced-change flag.
    $pdo->exec("CREATE TRIGGER stage6_fail_audit BEFORE INSERT ON audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test audit failure'");
    [$status,$json]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>$next,'new_password'=>$password],$csrf($origin,$forced));
    $check($status===500 && $json['error']['code']==='INTERNAL_ERROR','audit failure safe response');
    $pdo->exec('DROP TRIGGER stage6_fail_audit');
    $check($pdo->query("SELECT password_hash FROM users WHERE username='forced'")->fetchColumn()===$dbUser['password_hash'],'audit failure rolls back password');
    $freshToken=$csrf($origin,$forced);
    [$status]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>'wrong','new_password'=>$password],$freshToken); $check($status===403,'fifth bounded password attempt verified');
    [$status]=$request($origin,'/api/v1/account/password',$forced,'PATCH',['current_password'=>$next,'new_password'=>$password],$freshToken); $check($status===429,'persistent password-change throttle');
    $pdo->exec("UPDATE users SET role='Viewer' WHERE username='staff'"); [$status]=$request($origin,'/api/v1/suppliers',$jars['staff']); $check($status===403,'role reread next request');
    $pdo->exec("UPDATE users SET is_active=0 WHERE username='viewer'"); [$status]=$request($origin,'/api/v1/auth/me',$jars['viewer']); $check($status===401,'deactivation revokes active session');
    $pdo->exec('DELETE FROM rate_limits'); $inactive=[]; $token=$csrf($origin,$inactive);
    [$status,$json]=$request($origin,'/api/v1/auth/login',$inactive,'POST',['username'=>'viewer','password'=>$password],$token); $check($status===401 && $json['error']['message']===$wrong,'inactive login generic');
    [$status]=$request($origin,'/api/v1/auth/logout',$forced,'POST',null,'wrong'); $check($status===403,'logout invalid CSRF no effect');
    [$status]=$request($origin,'/api/v1/auth/me',$forced); $check($status===200,'invalid logout preserves session');
    $beforeLogout=$forced; [$status,$json,$headers]=$request($origin,'/api/v1/auth/logout',$forced,'POST',null,$csrf($origin,$forced));
    $check($status===200 && $json['data']['logged_out'],'real bodyless logout');
    $check(str_contains(implode(';',$headers),'Max-Age=0'),'logout expires cookie');
    [$status]=$request($origin,'/api/v1/auth/me',$beforeLogout); $check($status===401,'destroyed cookie cannot authenticate');
    [$status]=$request($origin,'/api/v1/auth/me',$anonymousCookie); $check($status===401,'prelogin cookie cannot authenticate');
    $short=$start(['SESSION_IDLE_SECONDS'=>'2','SESSION_ABSOLUTE_SECONDS'=>'4']); $expires=[]; $login($short,$expires,'admin',$password); sleep(2);
    [$status]=$request($short,'/api/v1/auth/me',$expires); $check($status===401,'actual inactivity expiry');
    $absolute=$start(['SESSION_IDLE_SECONDS'=>'10','SESSION_ABSOLUTE_SECONDS'=>'10']); $expires=[]; $login($absolute,$expires,'admin',$password);
    // Repeated valid activity keeps idle fresh but never resets authentication time.
    for($i=0;$i<5;$i++){sleep(2);[$status]=$request($absolute,'/api/v1/auth/me',$expires);$check($status===($i===4?401:200),'absolute expiry with active session');}
    $production=new Config($config->root,'production','/bizstock','PHP','Asia/Manila','127.0.0.1',3306,'unused','unused','',$config->logPath,'mysql84');
    $options=SessionManager::cookieOptions($production); $check($options['secure'] && $options['httponly'] && $options['samesite']==='Lax' && $options['path']==='/bizstock/','production cookie policy');
    $httpProduction=$start(['APP_ENV'=>'production','DB_TARGET'=>'mysql84']); $jar=[]; [$status,$json]=$request($httpProduction,'/api/v1/auth/csrf',$jar); $check($status===403 && $json['error']['code']==='HTTPS_REQUIRED','production HTTP session refusal without MySQL claim');
    $pdo->exec('DELETE FROM rate_limits'); $limited=[]; $token=$csrf($origin,$limited);
    for($i=0;$i<6;$i++){[$status,$json,$headers]=$request($origin,'/api/v1/auth/login',$limited,'POST',['username'=>'admin','password'=>'wrong'],$token);$check($status===($i<5?401:429),'persistent account rate limit');}
    $check((bool)array_filter($headers,static fn(string $h):bool=>str_starts_with($h,'Retry-After: ')),'rate limit retry-after');
    $remaining=(int)substr(current(array_filter($headers,static fn(string $h):bool=>str_starts_with($h,'Retry-After: '))),13);
    $check($remaining>0 && $remaining<=300,'login wait bounded to five minutes');
    $pdo->exec('UPDATE rate_limits SET reset_at=DATE_ADD(window_started_at,INTERVAL 15 MINUTE)');
    $secondOrigin=$start(); $second=[]; $token=$csrf($secondOrigin,$second); [$status]=$request($secondOrigin,'/api/v1/auth/login',$second,'POST',['username'=>'admin','password'=>$password],$token); $check($status===429,'rate limit survives another worker');
    $check((int)$pdo->query('SELECT MAX(TIMESTAMPDIFF(SECOND,window_started_at,reset_at)) FROM rate_limits')->fetchColumn()<=300,'existing fifteen-minute windows shortened without clearing attempts');
    $pdo->exec('UPDATE rate_limits SET window_started_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 30 MINUTE),reset_at=DATE_SUB(UTC_TIMESTAMP(6),INTERVAL 15 MINUTE)');
    [$status]=$request($secondOrigin,'/api/v1/auth/login',$second,'POST',['username'=>'admin','password'=>$password],$token); $check($status===200,'expired rate window resets safely');
    $pdo->exec('DELETE FROM rate_limits'); $peer=[]; $token=$csrf($origin,$peer);
    for($i=0;$i<31;$i++){[$status]=$request($origin,'/api/v1/auth/login',$peer,'POST',['username'=>'unknown'.$i,'password'=>'wrong'],$token);$check($status===($i<30?401:429),'peer throttles distributed usernames');}
    $check((int)$pdo->query('SELECT COUNT(*) FROM rate_limits')->fetchColumn()===31,'blocked peer cannot create more account buckets');
    $pdo->exec('DELETE FROM rate_limits');
    $environment=array_replace(getenv(),['STAGE6_ORIGIN'=>$origin,'STAGE6_PASSWORD'=>$password,'STAGE6_NEXT'=>$next]);
    $browser=proc_open(['node','tests/browser/auth.cjs'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$config->root,$environment,['bypass_shell'=>true]);
    fclose($pipes[0]); $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]); fclose($pipes[1]);fclose($pipes[2]);
    $exit=proc_close($browser); if($exit!==0){throw new RuntimeException('Browser acceptance failed: '.$err);} echo $out; $check(true,'real browser auth acceptance');
    $check((int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn()===0 && (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn()===0,'authentication never changes stock');
    echo 'PASS: '.$checks.' Stage 6 assertions on '.$pdo->query('SELECT VERSION()')->fetchColumn().'; disposable database only. MySQL 8.4 pending.'.PHP_EOL;
} finally {
    foreach($servers as $server){proc_terminate($server);proc_close($server);}
    if($created){$admin->exec("DROP DATABASE `{$db}`");}
    $sessionDir=$config->root.'/var/sessions/'.hash('sha256','test|'.$db.'|/bizstock');
    if(is_dir($sessionDir)){foreach(glob($sessionDir.'/sess_*') as $file){unlink($file);}rmdir($sessionDir);}
}
