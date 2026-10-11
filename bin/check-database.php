<?php
declare(strict_types=1);

try {
    $config = require dirname(__DIR__) . '/config/bootstrap.php';
    $factory = new \BizStock\Support\PdoFactory($config);
    $pdo = $factory->connection();
    $row = $pdo->query('SELECT VERSION() AS version, @@version_comment AS engine, @@session.time_zone AS timezone')->fetch();
    $row['target'] = $config->dbTarget;
    $row['detected_engine'] = $factory->platform()->engine;
    $row['scope'] = $config->dbTarget === 'mariadb104-local' ? 'local-development-only' : 'production-target';
    $row['verification'] = 'connection/session only; schema and application acceptance require separate evidence';
    echo json_encode($row, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable) {
    fwrite(STDERR, "Database check failed. Verify private configuration and explicit DB_TARGET (mysql84 or mariadb104-local in local/test only). Credentials and internal errors are withheld.\n");
    exit(1);
}
