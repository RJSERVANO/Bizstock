<?php
declare(strict_types=1);

use BizStock\Support\DatabasePlatform;
use BizStock\Support\CanonicalJson;

require dirname(__DIR__) . '/vendor/autoload.php';
$count = 0;
foreach ([
    ['10.4.32-MariaDB', 'mariadb.org binary distribution', 'mariadb104-local', 'local', true],
    ['5.5.5-10.4.32-MariaDB', 'MariaDB', 'mariadb104-local', 'test', true],
    ['10.4.32-MariaDB', 'MariaDB', 'mariadb104-local', 'production', false],
    ['10.4.32-MariaDB', 'MariaDB', 'mysql84', 'local', false],
    ['10.4.31-MariaDB', 'MariaDB', 'mariadb104-local', 'local', false],
    ['10.11.6-MariaDB', 'MariaDB', 'mariadb104-local', 'local', false],
    ['8.4.5', 'MySQL Community Server - GPL', 'mysql84', 'production', true],
    ['8.0.40', 'MySQL', 'mysql84', 'local', false],
    ['8.4.5', 'MySQL', 'mariadb104-local', 'test', false],
    ['8.4.5', 'Unknown engine', 'mysql84', 'local', false],
] as [$version, $comment, $target, $environment, $expected]) {
    try {
        DatabasePlatform::detect($version, $comment, $target, $environment);
        $allowed = true;
    } catch (RuntimeException) {
        $allowed = false;
    }
    if ($allowed !== $expected) {
        throw new RuntimeException('Version/engine/environment policy failed.');
    }
    $count++;
}
$a = CanonicalJson::encode(['sku' => 'TEST', 'nested' => ['b' => 2, 'a' => 1], 'price' => '245.00']);
$b = CanonicalJson::encode(['price' => '245.00', 'nested' => ['a' => 1, 'b' => 2], 'sku' => 'TEST']);
if ($a !== $b || CanonicalJson::encode(['a', 'b']) === CanonicalJson::encode(['b', 'a'])) {
    throw new RuntimeException('Canonical JSON order semantics failed.');
}
$count += 2;
try {
    CanonicalJson::encode(['price' => 1.5]);
    throw new RuntimeException('Float accepted.');
} catch (InvalidArgumentException) {
    $count++;
}
try {
    DatabasePlatform::jsonColumn('unsafe;DROP', 'ck_json');
    throw new RuntimeException('Unsafe schema identifier accepted.');
} catch (InvalidArgumentException) {
    $count++;
}
echo "PASS: {$count} engine/environment/canonical JSON policy assertions (MySQL server behavior still pending).\n";
