<?php
declare(strict_types=1);

$root = dirname(__DIR__);
foreach (['var/logs', 'var/sessions', 'public/assets/vendor'] as $directory) {
    $path = $root . '/' . $directory;
    if (!is_dir($path) && !mkdir($path, 0700, true)) {
        throw new RuntimeException('Cannot create private runtime directories.');
    }
}
if (!is_file($root . '/.env')) {
    if (!copy($root . '/.env.example', $root . '/.env')) {
        throw new RuntimeException('Cannot create local configuration.');
    }
}
echo "Local configuration and private runtime directories ready; no database or accounts created.\n";
