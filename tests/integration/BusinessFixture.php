<?php
declare(strict_types=1);
use BizStock\Support\{Config,PdoFactory,MigrationRunner,PasswordPolicy};

final class BusinessFixture
{
    public Config $config; public PDO $pdo; private PDO $admin; public string $password; public int $checks=0;
    private array $servers=[];public string $origin='';
    public function __construct(public string $stage)
    {
        $base=require dirname(__DIR__,2).'/config/bootstrap.php';
        if(!in_array($base->environment,['local','test'],true)||!in_array($base->dbHost,['127.0.0.1','localhost','::1'],true)){throw new RuntimeException('Disposable loopback test target required.');}
        $user=getenv('TEST_DB_ADMIN_USER');$password=getenv('TEST_DB_ADMIN_PASSWORD');
        if($user===false && $base->dbTarget==='mariadb104-local'){$user='root';$password=json_decode(file_get_contents($base->root.'/.tools/mariadb/credentials.json'),true,512,JSON_THROW_ON_ERROR)['root_password'];}
        if($user===false||$password===false){throw new RuntimeException('Private test administrator required.');}
        $name='bizstock_'.$stage.'_'.bin2hex(random_bytes(5));
        $make=fn($db)=>new Config($base->root,'test','/bizstock','PHP','Asia/Manila',$base->dbHost,$base->dbPort,$db,$user,$password,$base->logPath,$base->dbTarget);
        $this->admin=(new PdoFactory($make($base->dbName)))->connection();$this->admin->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->config=$make($name);$this->pdo=(new PdoFactory($this->config))->connection();
        (new MigrationRunner($this->pdo,$base->root.'/database/migrations'))->run();$this->password=bin2hex(random_bytes(16));
        $q=$this->pdo->prepare('INSERT INTO users(full_name,username,password_hash,role,must_change_password) VALUES (?,?,?,?,0)');
        foreach(['Admin','Staff','Staff','Admin','Viewer'] as $i=>$role){$q->execute([$role.' '.($i+1),'test'.($i+1),PasswordPolicy::hash($this->password),$role]);}
        $this->pdo->exec("INSERT INTO categories(category_name) VALUES ('General');INSERT INTO suppliers(supplier_name) VALUES ('General supplier')");
    }
    public function actor(int $id=1): array {$q=$this->pdo->prepare('SELECT * FROM users WHERE user_id=?');$q->execute([$id]);return $q->fetch();}
    public function product(string $sku='TEST',string $price='2.50',int $threshold=10): int
    { $q=$this->pdo->prepare('INSERT INTO products(sku,product_name,category_id,supplier_id,price,minimum_threshold) VALUES (?,?,1,1,?,?)');$q->execute([$sku,$sku,$price,$threshold]);return (int)$this->pdo->lastInsertId(); }
    public function check(bool $ok,string $label): void {if(!$ok){throw new RuntimeException('FAIL: '.$label);} $this->checks++;}
    public function reject(callable $action,int $status,string $label): void
    {try{$action();}catch(BizStock\Http\HttpException $e){$this->check($e->status===$status,$label);return;}throw new RuntimeException('FAIL accepted: '.$label);}
    public function environment(): array
    {return array_replace(getenv(),['APP_ENV'=>'test','APP_BASE_PATH'=>'/bizstock','APP_BUSINESS_NAME'=>'BizStock','APP_CONTACT_EMAIL'=>'','DB_TARGET'=>$this->config->dbTarget,'DB_HOST'=>$this->config->dbHost,'DB_PORT'=>(string)$this->config->dbPort,'DB_NAME'=>$this->config->dbName,'DB_USER'=>$this->config->dbUser,'DB_PASSWORD'=>$this->config->dbPassword]);}
    public function workers(array $jobs): array
    {
        $barrier=$this->config->root.'/var/'.$this->config->dbName.'.go';$processes=[];
        foreach($jobs as $tag=>$job){$env=$this->environment()+['TEST_BARRIER'=>$barrier,'TEST_TAG'=>(string)$tag];$proc=proc_open([PHP_BINARY,'tests/integration/business-worker.php',base64_encode(json_encode($job,JSON_THROW_ON_ERROR))],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$this->config->root,$env,['bypass_shell'=>true]);fclose($pipes[0]);$processes[]=[$proc,$pipes];}
        try{
            for($n=0;$n<200;$n++){if(count(glob($barrier.'.ready.*'))===count($jobs)){break;}usleep(10000);}file_put_contents($barrier,'go');$results=[];
            foreach($processes as [$proc,$pipes]){$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($proc);if($exit!==0){throw new RuntimeException('Worker failed: '.$err);}$results[]=json_decode($out,true,32,JSON_THROW_ON_ERROR);}return $results;
        }finally{foreach(glob($barrier.'*') as $file){unlink($file);}}
    }
    public function serve(): string
    {
        $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$error);$address=stream_socket_get_name($socket,false);fclose($socket);$port=substr($address,strrpos($address,':')+1);
        $process=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,'-t','public','bin/router.php'],[0=>['pipe','r'],1=>['file',$this->config->root.'/var/logs/business-tests.log','a'],2=>['file',$this->config->root.'/var/logs/business-tests.log','a']],$pipes,$this->config->root,$this->environment(),['bypass_shell'=>true]);fclose($pipes[0]);$this->servers[]=$process;
        for($i=0;$i<100;$i++){$stream=@stream_socket_client('tcp://127.0.0.1:'.$port,$code,$error,0.1);if($stream){fclose($stream);return $this->origin='http://127.0.0.1:'.$port.'/bizstock';}usleep(50000);}throw new RuntimeException('HTTP test server unavailable.');
    }
    public function http(string $path,array &$jar,string $method='GET',?array $body=null,?string $token=null,?string $key=null): array
    {
        $headers=['Accept: application/json'];if(isset($jar['cookie'])){$headers[]='Cookie: bizstock_session='.$jar['cookie'];}if($token!==null){$headers[]='X-CSRF-Token: '.$token;}if($key!==null){$headers[]='Idempotency-Key: '.$key;}
        if($body!==null){$headers[]='Content-Type: application/json';}
        $context=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$headers),'content'=>$body===null?'':json_encode($body===[]?(object)[]:$body,JSON_THROW_ON_ERROR),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>20]]);
        $raw=file_get_contents($this->origin.$path,false,$context);$responseHeaders=http_get_last_response_headers();preg_match('/ (\d{3}) /',$responseHeaders[0],$match);
        foreach($responseHeaders as $h){if(preg_match('/^Set-Cookie: bizstock_session=([^;]*)/i',$h,$m)){$jar['cookie']=$m[1];}}
        return [(int)$match[1],json_decode($raw,true),$responseHeaders,$raw];
    }
    public function login(int $actor=1): array
    {
        $jar=[];[, $json]=$this->http('/api/v1/auth/csrf',$jar);$token=$json['data']['csrf_token'];
        [$status]=$this->http('/api/v1/auth/login',$jar,'POST',['username'=>'test'.$actor,'password'=>$this->password],$token);$this->check($status===200,'actual HTTP login');
        [, $json]=$this->http('/api/v1/auth/csrf',$jar);$jar['token']=$json['data']['csrf_token'];return $jar;
    }
    public function browser(string $script): void
    {
        if($this->origin===''){$this->serve();}
        // One drained stream avoids deadlock when a large browser assertion fills stderr.
        $process=proc_open(['node',$script],[0=>['pipe','r'],1=>['pipe','w'],2=>['redirect',1]],$pipes,$this->config->root,array_replace(getenv(),['BUSINESS_ORIGIN'=>$this->origin,'BUSINESS_PASSWORD'=>$this->password]),['bypass_shell'=>true]);fclose($pipes[0]);$out=str_replace($this->password,'[redacted]',stream_get_contents($pipes[1]));fclose($pipes[1]);$exit=proc_close($process);if($exit!==0){throw new RuntimeException('Browser acceptance failed (exit '.$exit.', '.$script.'): '.$out);}echo $out;$this->check(true,'browser acceptance');
    }
    public function close(): void {
        foreach($this->servers as $server){proc_terminate($server);proc_close($server);}if($this->pdo->inTransaction()){$this->pdo->rollBack();}$this->admin->exec('DROP DATABASE `'.$this->config->dbName.'`');
        $directory=$this->config->root.'/var/sessions/'.hash('sha256','test|'.$this->config->dbName.'|/bizstock');if(is_dir($directory)){foreach(glob($directory.'/sess_*') as $file){unlink($file);}rmdir($directory);}
    }
}
