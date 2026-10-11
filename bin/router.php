<?php
declare(strict_types=1);

// Development server router. Never return arbitrary existing project files.
$root = dirname(__DIR__);
try {
    $config = require $root . '/config/bootstrap.php';
    $path = explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0];
    if ($config->basePath !== '' && str_starts_with($path, $config->basePath . '/')) {
        $path = substr($path, strlen($config->basePath));
    } elseif ($config->basePath !== '') {
        $path = '';
    }
    $allowed = ['assets/vendor/bootstrap.min.css' => 'text/css; charset=utf-8',
        'assets/vendor/bootstrap.bundle.min.js' => 'text/javascript; charset=utf-8',
        'assets/css/tokens.css' => 'text/css; charset=utf-8',
        'assets/css/components.css' => 'text/css; charset=utf-8',
        'assets/css/public.css' => 'text/css; charset=utf-8',
        'assets/images/stockroom-480.webp' => 'image/webp',
        'assets/images/stockroom-960.webp' => 'image/webp',
        'assets/images/stockroom-1440.webp' => 'image/webp',
        'assets/css/layout.css' => 'text/css; charset=utf-8',
        'assets/css/usability.css' => 'text/css; charset=utf-8',
        'assets/js/components.js' => 'text/javascript; charset=utf-8',
        'assets/js/images.js' => 'text/javascript; charset=utf-8',
        'assets/js/api-client.js' => 'text/javascript; charset=utf-8',
        'assets/js/business.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/products.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/product-form.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/movements.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/history.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/dashboard.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/reports.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/users.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/audit-events.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/auth.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/account.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/lookups.js' => 'text/javascript; charset=utf-8',
        'assets/js/pages/preview.js' => 'text/javascript; charset=utf-8'];
    $relative = ltrim($path, '/');
    if (isset($allowed[$relative]) && is_file($root . '/public/' . $relative)) {
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
            http_response_code(405);
            header('Allow: GET, HEAD');
            return true;
        }
        header('Content-Type: ' . $allowed[$relative]);
        header('X-Content-Type-Options: nosniff');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            readfile($root . '/public/' . $relative);
        }
        return true;
    }
} catch (Throwable) {
    // Front controller owns safe bootstrap error responses.
}
require $root . '/public/index.php';
return true;
