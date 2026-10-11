<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$count = 0;
foreach (['src', 'config', 'public', 'templates', 'bin', 'tests', 'database'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()), $status);
        if ($status !== 0) {
            exit($status);
        }
        $count++;
    }
}
echo "PASS: {$count} PHP files linted.\n";
