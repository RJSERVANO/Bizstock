<?php
declare(strict_types=1);

use BizStock\Http\ExceptionHandler;
use BizStock\Support\Config;
use BizStock\Support\PdoFactory;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$count = 0;
$check = static function (bool $condition, string $label) use (&$count): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $count++;
};
$request = static function (string $origin, string $path, string $method = 'GET'): array {
    $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true,
        'follow_location' => 0, 'timeout' => 5]]);
    $body = file_get_contents($origin . $path, false, $context);
    $headers = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $headers[0] ?? '', $match);
    return [(int) ($match[1] ?? 0), $body === false ? '' : $body, implode("\n", $headers)];
};
$server = static function (array $environment, callable $run) use ($root): void {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if ($socket === false) {
        throw new RuntimeException('Cannot allocate test port.');
    }
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=0', '-S', $address,
        '-t', $root . '/public', $root . '/bin/router.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $root . '/var/test-server.log', 'a'],
            2 => ['file', $root . '/var/test-server.log', 'a']], $pipes, $root,
        array_merge(getenv(), $environment));
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start test server.');
    }
    fclose($pipes[0]);
    try {
        $ready = false;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $probe = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
            if ($probe !== false) {
                fclose($probe);
                $ready = true;
                break;
            }
            usleep(100000);
        }
        if (!$ready) {
            throw new RuntimeException('Test server did not start.');
        }
        $run('http://' . $address);
    } finally {
        proc_terminate($process);
        proc_close($process);
    }
};

try {
    $config = Config::load($root);
    $check($config->currency === 'PHP' && $config->timezone === 'Asia/Manila', 'business defaults');
    $check(date_default_timezone_get() === 'UTC', 'storage timezone');
    $factory = new PdoFactory($config);
    $check($factory instanceof PdoFactory, 'PDO factory is lazy; liveness does not require DB');
    putenv('APP_BASE_PATH=/invalid/../path');
    try {
        Config::load($root);
        throw new RuntimeException('Invalid base path accepted.');
    } catch (InvalidArgumentException) {
        $check(true, 'invalid base path rejected');
    } finally {
        putenv('APP_BASE_PATH=');
    }
    putenv('DB_HOST=localhost;dbname=other');
    try {
        Config::load($root);
        throw new RuntimeException('DSN injection accepted.');
    } catch (InvalidArgumentException) {
        $check(true, 'DSN injection rejected');
    } finally {
        putenv('DB_HOST');
    }
    putenv('APP_LOG_PATH=public/leak.log');
    try {
        Config::load($root);
        throw new RuntimeException('Public log accepted.');
    } catch (InvalidArgumentException) {
        $check(true, 'public log rejected');
    } finally {
        putenv('APP_LOG_PATH');
    }
    $id = str_repeat('a', 32);
    $response = ExceptionHandler::response(new RuntimeException('SECRET password=example'), $id, $config->logPath);
    $check($response->status === 500 && !str_contains($response->body, 'SECRET'), 'internal errors sanitized');
    $log = file_get_contents($config->logPath);
    $check(str_contains($log, $id) && !str_contains($log, 'SECRET'), 'safe log with request ID');

    foreach (['', '/bizstock', '/nested/bizstock'] as $base) {
        $server(['APP_ENV' => 'test', 'APP_BASE_PATH' => $base],
            static function (string $origin) use ($base, $request, $check): void {
                [$status, $body, $headers] = $request($origin, $base . '/');
                $check($status === 200 && str_contains($body, 'Know your stock. Run your business with confidence.'), 'foundation page ' . $base);
                $check(str_contains($body, $base . '/assets/vendor/bootstrap.min.css')
                    && str_contains($body, $base . '/login'), 'base-aware URLs');
                $check(str_contains($headers, 'Content-Security-Policy:') && (str_contains($headers, 'Cache-Control: no-store') || str_contains($headers, 'Cache-Control: private, no-store')), 'HTTP security defaults');
                [$status, $body, $headers] = $request($origin, $base . '/api/v1/health');
                $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $check($status === 200 && $json === ['data' => ['status' => 'ok'], 'meta' => []], 'minimal liveness JSON');
                $check((bool) preg_match('/X-Request-ID: [a-f0-9]{32}/i', $headers), 'server-generated request ID');
                foreach(['unknown=1','page=1&page=2','q[]=x','q=%GG',str_repeat('a',4097)] as $query){
                    [$errorStatus,$errorBody,$errorHeaders]=$request($origin,$base.'/api/v1/health?'.$query);
                    $errorJson=json_decode($errorBody,true,512,JSON_THROW_ON_ERROR);
                    $check($errorStatus===(strlen($query)>4096?414:422)
                        && preg_match('/^[a-f0-9]{32}$/D',$errorJson['request_id'])
                        && str_contains($errorHeaders,'X-Request-ID: '.$errorJson['request_id']), 'malformed HTTP query and correlated error ID');
                }
                [$status, $body] = $request($origin, $base . '/api/v1/health', 'HEAD');
                $check($status === 200 && $body === '', 'HEAD body omitted');
                [$status, $body, $headers] = $request($origin, $base . '/api/v1/health', 'POST');
                $check($status === 405 && str_contains($headers, 'Allow: GET, HEAD'), 'method restriction');
                [$status,$asset,$assetHeaders]=$request($origin,$base.'/assets/js/api-client.js');
                $check($status===200&&str_contains($asset,'class ApiClient')&&str_contains($assetHeaders,'text/javascript'),'shared API client asset available');
                [$status, $body] = $request($origin, $base . '/assets/vendor/bootstrap.min.css');
                $check($status === 200 && (bool) preg_match('/Bootstrap\s+v5\.3\.8/', $body), 'pinned CSS served');
                [$status, $body] = $request($origin, $base . '/assets/vendor/bootstrap.bundle.min.js');
                $check($status === 200 && (bool) preg_match('/Bootstrap\s+v5\.3\.8/', $body), 'pinned JS served');
                foreach (['dashboard', 'products', 'products/new', 'login', 'components', 'viewer/dashboard', 'viewer/products'] as $preview) {
                    [$status, $body] = $request($origin, $base . '/preview/' . $preview);
                    $check($status === 200 && str_contains($body, 'Development preview.'), 'labeled fixture ' . $preview);
                }
                [$status, $body] = $request($origin, $base . '/preview/viewer/products');
                $check(!str_contains($body, '245.00') && !str_contains($body, 'Central Supply Co.') && !str_contains($body, 'data-open-dialog="archive-review">Review archive'), 'Viewer fixture fields stripped server-side');
                [$status] = $request($origin, $base . '/preview/products?state[]=error');
                $check($status === 400, 'invalid preview query rejected');
                foreach (['/.env', '/.git/config', '/composer.json', '/composer.lock', '/src/Support/Config.php',
                    '/config/bootstrap.php', '/vendor/autoload.php', '/docs/requirements.md', '/var/logs/app.log',
                    '/../.env', '/%2e%2e/.env', '/assets/vendor/../../.env', '/api/v1/unimplemented', '/api/v1/signup'] as $path) {
                    [$status, $body] = $request($origin, $base . $path);
                    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    $check($status === 404 && $json['error']['code'] === 'NOT_FOUND', 'private/unimplemented route ' . $path);
                }
                if ($base !== '') {
                    [$status, , $headers] = $request($origin, $base);
                    $check($status === 308 && str_contains($headers, 'Location: ' . $base . '/'), 'base slash redirect');
                    [$status] = $request($origin, '/api/v1/health');
                    $check($status === 404, 'route outside base denied');
                    [$status] = $request($origin, $base . '-other/api/v1/health');
                    $check($status === 404, 'prefix boundary enforced');
                }
            });
    }
    $server(['APP_ENV' => 'invalid-value', 'APP_BASE_PATH' => ''],
        static function (string $origin) use ($request, $check): void {
            [$status, $body] = $request($origin, '/api/v1/health');
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $check($status === 500 && $json['error']['code'] === 'INTERNAL_ERROR'
                && !str_contains($body, 'Invalid application environment'), 'bootstrap failure sanitized');
        });
    foreach ([['APP_ENV'=>'production','DB_TARGET'=>'mariadb104-local'],
        ['APP_ENV'=>'local','DB_TARGET'=>'mariadb104-local','DB_HOST'=>'192.0.2.1'],
        ['APP_ENV'=>'local','DB_TARGET'=>'unknown']] as $invalidDatabase) {
        $server($invalidDatabase + ['APP_BASE_PATH'=>''],
            static function (string $origin) use ($request, $check): void {
                [$status,$body]=$request($origin,'/api/v1/health');
                $json=json_decode($body,true,512,JSON_THROW_ON_ERROR);
                $check($status===500 && $json['error']['code']==='INTERNAL_ERROR'
                    && !str_contains($body,'database target'), 'unsafe database configuration rejected and sanitized');
            });
    }
    $server(['APP_ENV' => 'production', 'APP_BASE_PATH' => '', 'DB_TARGET' => 'mysql84'],
        static function (string $origin) use ($request, $check): void {
            foreach (['dashboard', 'products', 'products/new', 'login', 'components', 'viewer/products'] as $preview) {
                [$status] = $request($origin, '/preview/' . $preview);
                $check($status === 404, 'production fixture route denied');
            }
            [$status, $body] = $request($origin, '/');
            $check($status === 200 && !str_contains($body, '/preview/'), 'production home hides preview link');
        });
    echo "PASS: {$count} foundation assertions (HTTP root/subdirectory, isolation, config, errors, assets).\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
