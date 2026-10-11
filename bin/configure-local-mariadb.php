<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
$credentials = json_decode(file_get_contents($root . '/.tools/mariadb/credentials.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO('mysql:host=127.0.0.1;port=13307;charset=utf8mb4', 'root', $credentials['root_password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
$row = $pdo->query('SELECT VERSION() AS version, @@version_comment AS comment')->fetch(PDO::FETCH_ASSOC);
$platform = \BizStock\Support\DatabasePlatform::detect($row['version'], $row['comment'], 'mariadb104-local', 'local');
$platform->configure($pdo);
$pdo->exec('CREATE DATABASE IF NOT EXISTS bizstock_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$existing = is_file($root . '/.tools/mariadb/app.json');
$app = $existing ? json_decode(file_get_contents($root . '/.tools/mariadb/app.json'), true, 512, JSON_THROW_ON_ERROR)
    : ['password' => bin2hex(random_bytes(24))];
// Account/database names are fixed, and generated password is strictly hex, never supplied SQL text.
$pdo->exec("CREATE USER IF NOT EXISTS 'bizstock_dev'@'127.0.0.1' IDENTIFIED BY " . $pdo->quote($app['password']));
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON bizstock_local.* TO 'bizstock_dev'@'127.0.0.1'");
file_put_contents($root . '/.tools/mariadb/app.json', json_encode($app, JSON_THROW_ON_ERROR));
$file = $root . '/.env';
$text = file_get_contents(is_file($file) ? $file : $root . '/.env.example');
$values = ['DB_TARGET' => 'mariadb104-local', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '13307',
    'DB_NAME' => 'bizstock_local', 'DB_USER' => 'bizstock_dev', 'DB_PASSWORD' => $app['password']];
foreach ($values as $key => $value) {
    $pattern = '/^' . $key . '=.*$/m';
    $text = preg_match($pattern, $text) ? preg_replace($pattern, $key . '=' . $value, $text) : $text . "\n{$key}={$value}\n";
}
file_put_contents($file, $text);
echo "Actual MariaDB {$row['version']} verified; local account and database configured in ignored .env. No domain schema created.\n";
