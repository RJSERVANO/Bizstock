<?php
declare(strict_types=1);

namespace BizStock\Http;

final class ExceptionHandler
{
    public static function response(\Throwable $error, string $requestId, ?string $logPath): Response
    {
        $expected = $error instanceof HttpException;
        if (!$expected) {
            // Do not log messages, request bodies, DSNs or arguments: they may contain secrets.
            $entry = json_encode(['timestamp' => gmdate('c'), 'request_id' => $requestId,
                'exception' => get_class($error), 'file' => basename($error->getFile()),
                'line' => $error->getLine()], JSON_THROW_ON_ERROR) . PHP_EOL;
            if ($logPath !== null) {
                error_log($entry, 3, $logPath);
            } else {
                error_log($entry);
            }
        }
        $details=$expected?$error->details:[];
        return Response::json(['error' => ['code' => $expected ? $error->errorCode : 'INTERNAL_ERROR',
            'message' => $expected ? $error->getMessage() : 'An internal error occurred.'] + ($details!==[]?['details'=>$details]:[]),
            'request_id' => $requestId], $expected ? $error->status : 500,
            ['X-Request-ID' => $requestId, 'Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff'] + ($expected?$error->headers:[]));
    }
}
