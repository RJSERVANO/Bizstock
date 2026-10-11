<?php
declare(strict_types=1);
use BizStock\Support\{PdoFactory,AtomicWrite};
use BizStock\Repositories\AuthRepository;
use BizStock\Services\StockMovementService;
$config=require dirname(__DIR__,2).'/config/bootstrap.php';$pdo=(new PdoFactory($config))->connection();
$job=json_decode(base64_decode($argv[1],true),true,32,JSON_THROW_ON_ERROR);$actor=(new AuthRepository($pdo))->byId($job['actor']);
$barrier=getenv('TEST_BARRIER');file_put_contents($barrier.'.ready.'.getenv('TEST_TAG'),'ready');
for($i=0;!is_file($barrier)&&$i<2000;$i++){usleep(10000);}if(!is_file($barrier)){throw new RuntimeException('Worker barrier timeout.');}
try {
    if(isset($job['delay'])){usleep($job['delay']);}
    if(($job['action']??'movement')==='deadlock') {
        $calls=0;$result=AtomicWrite::run($pdo,$actor,['Admin','Staff'],function()use($pdo,$job,&$calls){$calls++;usleep(200000);$q=$pdo->prepare('SELECT user_id FROM users WHERE user_id=? FOR UPDATE');$q->execute([$job['other']]);return ['attempts'=>$calls];});
    }elseif(($job['action']??'')==='archive-probe'){
        $result=AtomicWrite::run($pdo,$actor,['Admin'],function()use($pdo,$job){$p=(new BizStock\Repositories\StockRepository($pdo))->product($job['product']);if($p['quantity']!==0){throw new BizStock\Http\HttpException(409,'CONFLICT','Stock is not zero.');}$q=$pdo->prepare('UPDATE products SET is_active=0,version=version+1 WHERE product_id=?');$q->execute([$job['product']]);return ['archived'=>true];});
    }elseif(($job['action']??'')==='user'){$result=(new BizStock\Services\UserService($pdo))->write($job['method'],$job['id'],$job['body'],$actor,'worker-'.bin2hex(random_bytes(16)));}
    elseif(($job['action']??'')==='create'){$result=(new BizStock\Services\ProductService($pdo))->create($job['body'],$actor,$job['key'],'worker-'.bin2hex(random_bytes(16)));}
    elseif(($job['action']??'')==='archive'){$result=(new BizStock\Services\ProductService($pdo))->mutate($job['product'],'DELETE',['expected_version'=>$job['version']],$actor,'worker-'.bin2hex(random_bytes(16)));}
    elseif(($job['action']??'')==='edit'){$result=(new BizStock\Services\ProductService($pdo))->mutate($job['product'],'PATCH',$job['body'],$actor,'worker-'.bin2hex(random_bytes(16)));}
    else {$pdo->exec('SET SESSION innodb_lock_wait_timeout=1');$result=(new StockMovementService($pdo))->record($job['body'],$actor,$job['key']);}
    echo json_encode(['status'=>201,'result'=>$result],JSON_THROW_ON_ERROR);
}catch(BizStock\Http\HttpException $e){echo json_encode(['status'=>$e->status,'code'=>$e->errorCode],JSON_THROW_ON_ERROR);}
