<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
\BizStock\Support\Cli::run(static function():int{
    $config=require dirname(__DIR__).'/config/bootstrap.php';
    $result=(new \BizStock\Support\StockReconciliation((new \BizStock\Support\PdoFactory($config))->connection()))->inspect();
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
    return $result['discrepancies']===[]?0:1;
});
