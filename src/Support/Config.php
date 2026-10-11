<?php
declare(strict_types=1);

namespace BizStock\Support;

use Dotenv\Dotenv;
use InvalidArgumentException;

final readonly class Config
{
    public function __construct(
        public string $root,
        public string $environment,
        public string $basePath,
        public string $currency,
        public string $timezone,
        public string $dbHost,
        public int $dbPort,
        public string $dbName,
        public string $dbUser,
        public string $dbPassword,
        public string $logPath,
        public string $dbTarget = 'mysql84',
        public int $sessionIdleSeconds = 1800,
        public int $sessionAbsoluteSeconds = 28800,
        public string $businessName = 'BizStock',
        public string $contactEmail = '',
    ) {}

    public static function load(string $root): self
    {
        Dotenv::createUnsafeImmutable($root)->safeLoad();
        $get = static function (string $key, string $default): string {
            $value = getenv($key);
            return $value !== false ? $value : (string) ($_ENV[$key] ?? $_SERVER[$key] ?? $default);
        };
        $environment = $get('APP_ENV', 'local');
        if (!in_array($environment, ['local', 'test', 'production'], true)) {
            throw new InvalidArgumentException('Invalid application environment.');
        }
        $base = $get('APP_BASE_PATH', '');
        if ($base !== '' && !preg_match('~^/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$~D', $base)) {
            throw new InvalidArgumentException('Invalid application base path.');
        }
        $port = $get('DB_PORT', '3306');
        $target = $get('DB_TARGET', 'mysql84');
        if (!in_array($target, ['mysql84', 'mariadb104-local'], true)
            || ($target === 'mariadb104-local' && $environment === 'production')) {
            throw new InvalidArgumentException('Invalid database target for this environment.');
        }
        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            throw new InvalidArgumentException('Invalid database port.');
        }
        $host = $get('DB_HOST', '127.0.0.1');
        $name = $get('DB_NAME', 'bizstock');
        if (!preg_match('/^[A-Za-z0-9._:-]+$/D', $host) || !preg_match('/^[A-Za-z0-9_]+$/D', $name)) {
            throw new InvalidArgumentException('Invalid database address.');
        }
        if ($target === 'mariadb104-local' && !in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new InvalidArgumentException('The MariaDB local target requires a loopback database host.');
        }
        $currency = $get('APP_CURRENCY', 'PHP');
        $timezone = $get('APP_TIMEZONE', 'Asia/Manila');
        if ($currency !== 'PHP' || $timezone !== 'Asia/Manila') {
            throw new InvalidArgumentException('Unsupported business currency or timezone.');
        }
        $log = $get('APP_LOG_PATH', 'var/logs/app.log');
        if (!preg_match('~^(?:[A-Za-z]:[\\/]|/)~', $log)) {
            $log = $root . '/' . $log;
        }
        // Resolve the directory to prohibit logs below the web document root.
        $directory = realpath(dirname($log));
        $public = realpath($root . '/public');
        if ($directory === false || $public === false || !is_writable($directory)) {
            throw new InvalidArgumentException('Private log directory must exist and be writable; run setup.');
        }
        $normalized = strtolower(str_replace('\\', '/', $directory));
        $web = strtolower(str_replace('\\', '/', $public));
        if ($normalized === $web || str_starts_with($normalized, $web . '/')) {
            throw new InvalidArgumentException('Logs must be outside the document root.');
        }
        date_default_timezone_set('UTC');
        $idle=$get('SESSION_IDLE_SECONDS','1800');
        $absolute=$get('SESSION_ABSOLUTE_SECONDS','28800');
        $minimum=$environment==='test'?1:300;
        if (!ctype_digit($idle) || !ctype_digit($absolute) || (int)$idle<$minimum || (int)$idle>86400
            || (int)$absolute<(int)$idle || (int)$absolute>604800) {
            throw new InvalidArgumentException('Invalid session expiry bounds.');
        }
        $businessName=trim($get('APP_BUSINESS_NAME','BizStock'));
        $contactEmail=trim($get('APP_CONTACT_EMAIL',''));
        if ($businessName==='' || mb_strlen($businessName)>100 || !mb_check_encoding($businessName,'UTF-8')
            || preg_match('/[\x00-\x1f\x7f]/',$businessName)
            || ($contactEmail!=='' && (strlen($contactEmail)>254 || !filter_var($contactEmail,FILTER_VALIDATE_EMAIL)))) {
            throw new InvalidArgumentException('Invalid public business name or contact email.');
        }
        return new self($root, $environment, $base, $currency, $timezone, $host, (int) $port,
            $name, $get('DB_USER', 'bizstock'), $get('DB_PASSWORD', ''), $log, $target,(int)$idle,(int)$absolute,$businessName,$contactEmail);
    }

    public function url(string $path): string
    {
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw new InvalidArgumentException('Expected a local absolute path.');
        }
        return $this->basePath . $path;
    }
}
