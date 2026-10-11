<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';require __DIR__.'/BusinessFixture.php';
use BizStock\Support\{MigrationRunner,StockReconciliation};
use BizStock\Services\ImageService;
$f=new BusinessFixture('images');
try{
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'reconciliation before');
 $f->check(count((new MigrationRunner($f->pdo,$f->config->root.'/database/migrations'))->run())===4,'safe rerun four migrations');
 $id=$f->product('IMAGE-TEST');$f->serve();$admin=$f->login();$staff=$f->login(2);$viewer=$f->login(5);
 $file=$f->config->root.'/var/image-test-'.bin2hex(random_bytes(8)).'.png';$image=imagecreatetruecolor(80,60);imagepng($image,$file);unset($image);
 $upload=function(string $path,array $jar,string $bytes,string $filename='photo.png',bool $csrf=true)use($f):array{
  $boundary='image'.bin2hex(random_bytes(16));$body="--{$boundary}\r\nContent-Disposition: form-data; name=\"image\"; filename=\"{$filename}\"\r\nContent-Type: image/png\r\n\r\n".$bytes."\r\n--{$boundary}--\r\n";
  $headers=['Content-Type: multipart/form-data; boundary='.$boundary,'Cookie: bizstock_session='.($jar['cookie']??'')];if($csrf)$headers[]='X-CSRF-Token: '.($jar['token']??'');
  $raw=file_get_contents($f->origin.$path,false,stream_context_create(['http'=>['method'=>'POST','header'=>implode("\r\n",$headers),'content'=>$body,'ignore_errors'=>true,'timeout'=>20]]));$headers=http_get_last_response_headers();preg_match('/ (\d{3}) /',$headers[0],$m);return [(int)$m[1],json_decode($raw,true)];
 };
 $png=file_get_contents($file);$product='/api/v1/products/'.$id.'/image';
 $f->check($upload($product,[],$png)[0]===401,'anonymous upload denied');
 foreach([$staff,$viewer] as $jar){$f->check($upload($product,$jar,$png)[0]===403,'non-Admin product upload denied');}
 $f->check($upload($product,$admin,$png,'photo.png',false)[0]===403,'CSRF denied');
 foreach(['<svg xmlns="http://www.w3.org/2000/svg"></svg>','<?php echo 1;', $png.'<?php echo 1;',str_repeat('x',2097153),substr($png,0,20)] as $bytes){$f->check($upload($product,$admin,$bytes)[0]===422,'invalid image rejected');}
 $large=imagecreatetruecolor(2049,2);ob_start();imagepng($large);$bytes=ob_get_clean();unset($large);$f->check($upload($product,$admin,$bytes)[0]===422,'dimension bound');
 foreach(['imagejpeg','imagepng','imagewebp'] as $encoder){$i=imagecreatetruecolor(40,30);ob_start();$encoder($i);$bytes=ob_get_clean();unset($i);$f->check($upload($product,$admin,$bytes)[0]===200,'JPEG PNG WebP decoded');}
 $name=$f->pdo->query('SELECT product_image FROM products WHERE product_id='.$id)->fetchColumn();$store=(new ImageService($f->pdo,$f->config))->directory();$f->check(preg_match('/^[a-f0-9]{32}\.webp$/D',$name)===1,'generated filename');
 $f->check($upload($product,$admin,$png)[0]===200,'replacement');$f->check(!file_exists($store.'/'.$name),'replaced file removed');
 $f->check($upload($product,$admin,$png.'PRIVATE_METADATA_SENTINEL')[0]===200,'metadata input accepted for stripping');
 $f->check(!str_contains((new ImageService($f->pdo,$f->config))->read(true,$id),'PRIVATE_METADATA_SENTINEL'),'metadata stripped from output');
 $f->check($f->http('/api/v1/users/2/photo',$admin)[0]===404,'no arbitrary-user photo endpoint');
 $f->pdo->exec('UPDATE users SET must_change_password=1 WHERE user_id=5');
 $f->check($upload('/api/v1/account/photo',$viewer,$png)[0]===403,'forced-change upload denied');$f->pdo->exec('UPDATE users SET must_change_password=0 WHERE user_id=5');
 $otherAdmin=$f->login(4);$origin1=$f->origin;$origin2=$f->serve();$multi=curl_multi_init();$handles=[];
 foreach([[$origin1,$admin],[$origin2,$otherAdmin]] as [$origin,$jar]){$ch=curl_init($origin.$product);curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>['image'=>new CURLFile($file,'image/png','photo.png')],CURLOPT_HTTPHEADER=>['Cookie: bizstock_session='.$jar['cookie'],'X-CSRF-Token: '.$jar['token']],CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20]);curl_multi_add_handle($multi,$ch);$handles[]=$ch;}
 do{$code=curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,1);}while($running&&$code===CURLM_OK);
 foreach($handles as $ch){$f->check(curl_getinfo($ch,CURLINFO_HTTP_CODE)===200,'concurrent independent-server replacement');curl_multi_remove_handle($multi,$ch);}curl_multi_close($multi);
 $current=$f->pdo->query('SELECT product_image FROM products WHERE product_id='.$id)->fetchColumn();$f->check(count(glob($store.'/*.webp'))===1&&is_file($store.'/'.$current),'concurrent replacements retain exactly current file');
 $f->check($f->http('/var/uploads/'.basename($store).'/'.$current,$viewer)[0]===404,'private file has no public static route');
 $f->pdo->exec("CREATE TRIGGER images_reject_audit BEFORE INSERT ON audit_events FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='isolated audit rejection'");
 $f->check($upload($product,$admin,$png)[0]===500,'audit failure refuses upload success');$f->pdo->exec('DROP TRIGGER images_reject_audit');
 $f->check($f->pdo->query('SELECT product_image FROM products WHERE product_id='.$id)->fetchColumn()===$current&&count(glob($store.'/*.webp'))===1,'audit rollback preserves reference and cleans staged file');
 [$status,$data,$headers,$raw]=$f->http($product,$viewer);$f->check($status===200&&str_starts_with($raw,'RIFF')&&str_contains(implode(' ',$headers),'private, no-store'),'Viewer permitted image controlled WebP response');
 $none=[];$f->check($f->http($product, $none)[0]===401,'anonymous read denied');
 foreach([$admin,$staff,$viewer] as $jar){$f->check($upload('/api/v1/account/photo',$jar,$png)[0]===200,'own profile upload all roles');$f->check($f->http('/api/v1/auth/me',$jar)[1]['data']['has_image']===true,'profile flag only');$f->check($f->http('/api/v1/account/photo',$jar,'DELETE',null,$jar['token'])[0]===200,'own photo removal');$f->check($f->http('/api/v1/account/photo',$jar)[0]===404,'default avatar after removal');}
 $f->check($f->http($product,$staff,'DELETE',null,$staff['token'])[0]===403,'non-Admin removal denied');
 $f->check($f->http($product,$admin,'DELETE',null,$admin['token'])[0]===200,'product removal');$f->check(glob($store.'/*.webp')===[],'no leftover image files');
 $f->pdo->exec("UPDATE products SET is_active=0 WHERE product_id={$id}");$f->check($upload($product,$admin,$png)[0]===409,'archive image mutation conflict');$f->check(glob($store.'/*.webp')===[],'failed mutation cleans staged file');$f->pdo->exec("UPDATE products SET is_active=1 WHERE product_id={$id}");
 $f->check($f->pdo->query('SELECT quantity FROM products WHERE product_id='.$id)->fetchColumn()===0,'images do not change stock');
 $f->check((new StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'reconciliation after');
 putenv('IMAGE_FIXTURE_FILE='.$file);$f->browser('tests/browser/images.cjs');echo 'PASS: '.$f->checks." image/database/HTTP assertions.\n";
}finally{if(isset($file)&&is_file($file))unlink($file);if(isset($store)&&is_dir($store)){foreach(glob($store.'/*.webp') as $p)unlink($p);rmdir($store);}$f->close();}
