<?php
declare(strict_types=1);require dirname(__DIR__).'/vendor/autoload.php';
BizStock\Support\Cli::run(static function()use($argv):int{
 if(count($argv)!==4||$argv[3]!=='--password-stdin')throw new InvalidArgumentException('Usage: recover-admin.php user_id "Reason" --password-stdin. Trusted operator only; password on stdin.');
 $password=fgets(STDIN,1024);if($password===false)throw new InvalidArgumentException('Password missing.');$config=require dirname(__DIR__).'/config/bootstrap.php';$pdo=(new BizStock\Support\PdoFactory($config))->connection();(new BizStock\Services\UserService($pdo))->recover(BizStock\Support\ApiInput::integer($argv[1],'user_id',1),rtrim($password,"\r\n"),$argv[2]);unset($password);echo "Existing account recovered as Admin; all prior sessions revoked; change password at login.\n";return 0;
});
