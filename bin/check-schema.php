<?php
declare(strict_types=1);require dirname(__DIR__).'/vendor/autoload.php';
BizStock\Support\Cli::run(static function():int{
 $config=require dirname(__DIR__).'/config/bootstrap.php';$pdo=(new BizStock\Support\PdoFactory($config))->connection();$applied=$pdo->query('SELECT migration_id,checksum,status FROM schema_migrations ORDER BY migration_id')->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);$files=[];foreach(glob($config->root.'/database/migrations/*.sql') as $file)$files[basename($file)]=$file;
 foreach($applied as $id=>$row)if(!isset($files[$id])||$row['status']!=='applied'||!hash_equals($row['checksum'],hash_file('sha256',$files[$id])))throw new RuntimeException('Migration history requires operator review; no changes were made.');
 $pending=array_values(array_diff(array_keys($files),array_keys($applied)));echo json_encode(['applied'=>array_keys($applied),'pending'=>$pending,'ready'=>$pending===[],'scope'=>'Read-only schema check. No migrations applied.'],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;return $pending===[]?0:2;
});
