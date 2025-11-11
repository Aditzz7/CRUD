<?php
declare(strict_types=1);

namespace App;

final class HttpResponse
{
    public static function json(int $status, array $data = []): void
    {
        http_response_code($status);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function empty(int $status): void
    {
        http_response_code($status);
    }
}
