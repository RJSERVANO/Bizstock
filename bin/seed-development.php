<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
\BizStock\Support\Cli::run(static function():int{
    $config=require dirname(__DIR__).'/config/bootstrap.php';
    if(!in_array($config->environment,['local','test'],true)){throw new RuntimeException('Development seeding is prohibited in production.');}
    $file=$config->root.'/var/development-credentials.json';
    $pdo=(new \BizStock\Support\PdoFactory($config))->connection();
    // Preserve credentials on rerun; never print or replace them.
    $done=(int)$pdo->query("SELECT COUNT(*) FROM audit_events WHERE action='development.seed'")->fetchColumn();
    if($done>0){echo "Already seeded; no data or credentials changed.\n";return 0;}
    $handle=fopen($file,'x');
    if($handle===false){throw new RuntimeException('Private credential file already exists or is unwritable; review before seeding.');}
    chmod($file,0600);
    try{
        $result=(new \BizStock\Services\DevelopmentSeeder($pdo,$config))->run(static function(array $credentials)use($handle):void{
            $text=json_encode($credentials,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
            if(fwrite($handle,$text)!==strlen($text) || !fflush($handle)){throw new RuntimeException('Private credential write failed; seed transaction aborted.');}
        });
        fclose($handle);
        if($result['status']==='already seeded'){unlink($file);echo "Already seeded; no data or credentials changed.\n";return 0;}
        echo "Development seeds committed; random credentials stored privately in ignored var/development-credentials.json.\n";
    }catch(Throwable $error){if(is_resource($handle)){fclose($handle);}unlink($file);throw $error;}
    return 0;
});
