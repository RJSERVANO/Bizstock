<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
$requestId = bin2hex(random_bytes(16));
$config = null;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $config = require dirname(__DIR__) . '/config/bootstrap.php';
    $response = (new \BizStock\Http\Application($config))->handle(
        $_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/', $requestId);
} catch (Throwable $error) {
    restore_error_handler();
    if (class_exists(\BizStock\Http\ExceptionHandler::class)) {
        $response = \BizStock\Http\ExceptionHandler::response($error, $requestId, $config?->logPath);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Request-ID: ' . $requestId);
        header('Cache-Control: no-store');
        echo json_encode(['error' => ['code' => 'INTERNAL_ERROR', 'message' => 'An internal error occurred.'],
            'request_id' => $requestId]);
        exit;
    }
}
if (session_status()===PHP_SESSION_ACTIVE) { session_write_close(); }
header('X-Request-ID: ' . $requestId);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; img-src 'self' blob:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'");
header('Referrer-Policy: same-origin');
$response->send(($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD');
