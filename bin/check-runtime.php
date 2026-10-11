<?php
declare(strict_types=1);

$ok = PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 5;
echo 'PHP ' . PHP_VERSION . ($ok ? " (target 8.5)\n" : " (requires PHP 8.5)\n");
foreach (['pdo', 'pdo_mysql', 'mbstring', 'openssl','gd','fileinfo'] as $extension) {
    $present = extension_loaded($extension);
    echo $extension . ': ' . ($present ? 'available' : 'MISSING') . PHP_EOL;
    $ok = $ok && $present;
}
if(extension_loaded('gd')){$webp=(bool)(gd_info()['WebP Support']??false);echo 'GD WebP codec: '.($webp?'available':'MISSING').PHP_EOL;$ok=$ok&&$webp;}
exit($ok ? 0 : 1);
