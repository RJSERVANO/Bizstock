<?php
declare(strict_types=1);
namespace BizStock\Support;

final class Cli
{
    public static function run(callable $command): never
    {
        if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
        try { exit((int)$command()); }
        catch(\PDOException){fwrite(STDERR,"Database command failed; verify connection/privileges and migration status. Internal SQL and credentials withheld.\n");exit(2);}
        catch(\InvalidArgumentException|\RuntimeException $error){fwrite(STDERR,$error->getMessage().PHP_EOL);exit(2);}
        catch(\Throwable){fwrite(STDERR,"Command failed; internal details withheld.\n");exit(2);}
    }
}
