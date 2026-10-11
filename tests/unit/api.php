<?php
declare(strict_types=1);
use BizStock\Support\ApiInput;
use BizStock\Http\{Application,ExceptionHandler,HttpException};
$config=require dirname(__DIR__,2).'/config/bootstrap.php';$count=0;
$check=static function(bool $ok,string $name)use(&$count):void{if(!$ok){throw new RuntimeException('FAIL: '.$name);} $count++;};
$reject=static function(callable $run,int $status)use($check):void{try{$run();}catch(HttpException $e){$check($e->status===$status,'typed rejection '.$status);return;}throw new RuntimeException('Unexpected acceptance');};
foreach(['1e2','01','-1','2147483648',true,1.5,[],null] as $value){$reject(fn()=>ApiInput::integer($value,'quantity'),422);}
$check(ApiInput::integer('2147483647','quantity')===2147483647,'integer bound');
foreach(['1',1.50,'01.00','100000000.00','-0.01'] as $value){$reject(fn()=>ApiInput::decimal($value,'price'),422);}
$check(ApiInput::decimal('99999999.99','price')==='99999999.99','lossless max decimal');
foreach(['q=a&q=b','q[]=a','unknown=a','q=%GG','q=%00','q=%FF'] as $query){$reject(fn()=>ApiInput::query($query,['q']),422);}
$check(ApiInput::query('q=Caf%C3%A9',['q'])===['q'=>'Café'],'Unicode query');
$reject(fn()=>ApiInput::query(str_repeat('a',4097),[]),414);
foreach(['null','[]','{broken','"string"'] as $body){$reject(fn()=>ApiInput::json($body,'application/json',['name']),400);}
foreach(['{"name":"one","name":"two"}','{"name":{"a":1,"a":2}}','{"name":1,"\\u006eame":2}'] as $body){$reject(fn()=>ApiInput::json($body,'application/json',['name']),400);}
$reject(fn()=>ApiInput::json('{}','text/plain',[]),415);
$reject(fn()=>ApiInput::json(str_repeat(' ',65537),'application/json',[]),413);
$reject(fn()=>ApiInput::json('{"actor":1}','application/json',['name']),422);
$check(ApiInput::json('{"name":"Office"}','application/json; charset=UTF-8',['name'])===['name'=>'Office'],'JSON whitelist');
$page=ApiInput::page([],['name'=>'p.product_name'],'name');$check($page['per_page']===25&&$page['offset']===0,'page defaults');
$page=ApiInput::page(['page'=>'10000','per_page'=>'100','direction'=>'desc'],['name'=>'p.product_name'],'name');$check($page['offset']===999900&&$page['order_by']==='p.product_name'&&$page['direction']==='DESC','bounded server sort map');
foreach([['page'=>'0'],['page'=>'10001'],['per_page'=>'101'],['sort'=>'name;DROP'],['direction'=>'DESC']] as $query){$reject(fn()=>ApiInput::page($query,['name'=>'p.product_name'],'name'),422);}
$reject(fn()=>ApiInput::operationKey("key\n"),422);$check(ApiInput::operationKey('test:one-1')==='test:one-1','operation key');
$reject(fn()=>ApiInput::text("secret\0",'name',100),422);
$check(ApiInput::boolean('false','active')===false,'query boolean');$reject(fn()=>ApiInput::boolean(1,'active'),422);
$check(ApiInput::choice('IN','type',['IN','OUT'])==='IN','allowlisted enum');$reject(fn()=>ApiInput::choice('in','type',['IN','OUT']),422);
foreach([['date_from'=>'2026-02-30'],['date_from'=>'2026-10-12','date_to'=>'2026-10-11'],['date_from'=>'2025-01-01','date_to'=>'2026-01-02']] as $query){$reject(fn()=>ApiInput::dateRange($query),422);}
$check(ApiInput::dateRange(['date_from'=>'2024-02-29','date_to'=>'2024-03-01'])['date_from']==='2024-02-29','leap-date range');
$app=new Application($config);$id=str_repeat('a',32);
$router=new \BizStock\Http\ApiRouter();$router->add('/api/v1/test/{id}',['GET'],static fn(array $params)=>\BizStock\Http\Response::success($params));
$check(json_decode($router->dispatch('GET','/api/v1/test/42')->body,true)['data']['id']===42,'bounded route parameter');
$reject(fn()=>$router->dispatch('GET','/api/v1/test/2147483648'),422);
$reject(fn()=>$router->dispatch('GET','/api/v1/test/0'),404);
$health=$app->handle('GET',$config->url('/api/v1/health'),$id);$check(json_decode($health->body,true)===['data'=>['status'=>'ok'],'meta'=>[]],'unchanged health contract');
foreach(['/unimplemented','/signup','/auth/signup','/not-users','/not-transactions'] as $path){$reject(fn()=>$app->handle('POST',$config->url('/api/v1'.$path),$id),404);}
$reject(fn()=>$app->handle('GET',$config->url('/api/v1/health?unknown=1'),$id),422);
try{$app->handle('POST',$config->url('/api/v1/health'),$id);}catch(HttpException $e){$response=ExceptionHandler::response($e,$id,$config->logPath);$check($response->status===405&&$response->headers['Allow']==='GET, HEAD','central method mapping');}
$response=ExceptionHandler::response(new HttpException(422,'VALIDATION_ERROR','Request validation failed.',['price'=>['Invalid decimal.']]),$id,$config->logPath);
$check(json_decode($response->body,true)['error']['details']['price']===['Invalid decimal.'],'safe field details');
echo "PASS: {$count} API validation/router/envelope assertions.\n";
