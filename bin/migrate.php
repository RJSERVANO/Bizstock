<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
\BizStock\Support\Cli::run(static function():int{
    $config=require dirname(__DIR__).'/config/bootstrap.php';
    $pdo=(new \BizStock\Support\PdoFactory($config))->connection();
    $result=(new \BizStock\Support\MigrationRunner($pdo,$config->root.'/database/migrations'))->run();
    foreach($result as $id=>$status){echo $id.': '.$status.PHP_EOL;}
    return 0;
});
