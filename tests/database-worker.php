<?php
declare(strict_types=1);

// Invoked by compatibility suite; only generated fixture tables, never domain stock.
$config = require dirname(__DIR__) . '/config/bootstrap.php';
[$script, $table, $ready, $result] = $argv;
if (!preg_match('/^compat_[a-f0-9]{12}_ledger$/D', $table) || !str_starts_with($ready, $config->root . '/var/') || !str_starts_with($result, $config->root . '/var/')) {
    throw new RuntimeException('Unsafe test worker arguments.');
}
$pdo = (new \BizStock\Support\PdoFactory($config))->connection();
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 3');
file_put_contents($ready, 'ready');
try {
    $pdo->beginTransaction();
    $quantity = (int) $pdo->query("SELECT quantity FROM `{$table}` WHERE id=1 FOR UPDATE")->fetchColumn();
    if ($quantity < 4) {
        $pdo->rollBack();
        file_put_contents($result, 'insufficient');
    } else {
        $pdo->exec("UPDATE `{$table}` SET quantity=quantity-4 WHERE id=1");
        $pdo->commit();
        file_put_contents($result, 'updated');
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    file_put_contents($result, 'failed');
    exit(1);
}
