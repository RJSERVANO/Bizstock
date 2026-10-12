<?php
declare(strict_types=1);require __DIR__.'/BusinessFixture.php';
$f=new BusinessFixture('visual_consistency');$file=$f->config->root.'/var/consistency-'.bin2hex(random_bytes(8)).'.png';
try{
 $products=new BizStock\Services\ProductService($f->pdo);
 foreach([0,5,8] as $i=>$qty)$products->create(['sku'=>'VISUAL-'.($i+1),'product_name'=>$i===2?str_repeat('Organized inventory ',7):'Product '.($i+1),'category_id'=>1,'supplier_id'=>1,'price'=>'25.00','minimum_threshold'=>5,'opening_quantity'=>$qty],$f->actor(),'visual-'.$i,'consistency');
 $image=imagecreatetruecolor(640,320);imagefill($image,0,0,imagecolorallocate($image,225,237,239));imagefilledrectangle($image,160,40,480,280,imagecolorallocate($image,30,90,100));imagepng($image,$file);unset($image);putenv('CONSISTENCY_IMAGE_FILE='.$file);
 $f->serve();$f->browser('tests/browser/visual-consistency.cjs');$f->check((new BizStock\Support\StockReconciliation($f->pdo))->inspect()['discrepancies']===[],'isolated stock reconciles');echo 'PASS: '.$f->checks.' visual consistency fixture assertions.'.PHP_EOL;
}finally{putenv('CONSISTENCY_IMAGE_FILE');if(is_file($file))unlink($file);$service=new BizStock\Services\ImageService($f->pdo,$f->config);foreach($f->pdo->query('SELECT product_id FROM products WHERE product_image IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) as $id)$service->change(true,$id,$f->actor(),null,'fixture-cleanup');$directory=$service->directory();if(is_dir($directory)&&count(scandir($directory))===2)rmdir($directory);$f->close();}
