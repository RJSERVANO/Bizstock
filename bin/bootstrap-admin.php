<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
\BizStock\Support\Cli::run(static function() use($argv):int{
    if(count($argv)!==4 || $argv[3]!=='--password-stdin'){throw new InvalidArgumentException('Usage: bootstrap-admin.php username "Full name" --password-stdin (prefer hidden-input PowerShell wrapper). Never put passwords in arguments.');}
    $password=fgets(STDIN,1024);
    if($password===false){throw new InvalidArgumentException('Password input missing.');}
    $password=rtrim($password,"\r\n");
    $config=require dirname(__DIR__).'/config/bootstrap.php';
    $pdo=(new \BizStock\Support\PdoFactory($config))->connection();
    $id=(new \BizStock\Services\AdminBootstrap($pdo))->create($argv[1],$argv[2],$password);
    unset($password);echo 'Initial Admin created (user_id '.$id.'); password change required. No password printed.'.PHP_EOL;
    return 0;
});
