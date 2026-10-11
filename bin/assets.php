<?php
declare(strict_types=1);

// Version is locked by Composer; official SRI fingerprints verify copied bundles.
$root = dirname(__DIR__);
$files = [
    'css/bootstrap.min.css' => 'sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB',
    'js/bootstrap.bundle.min.js' => 'FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI',
];
$destination = $root . '/public/assets/vendor';
if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
    throw new RuntimeException('Cannot create asset directory.');
}
foreach ($files as $file => $hash) {
    $source = $root . '/vendor/twbs/bootstrap/dist/' . $file;
    if (!is_file($source) || !hash_equals($hash, base64_encode(hash_file('sha384', $source, true)))) {
        throw new RuntimeException('Bootstrap asset integrity verification failed.');
    }
    if (!copy($source, $destination . '/' . basename($file))) {
        throw new RuntimeException('Cannot install asset.');
    }
}
if (!copy($root . '/vendor/twbs/bootstrap/LICENSE', $destination . '/LICENSE.bootstrap')) {
    throw new RuntimeException('Cannot install Bootstrap license.');
}
echo "PASS: Bootstrap 5.3.8 bundles copied with official SHA-384 verification.\n";
