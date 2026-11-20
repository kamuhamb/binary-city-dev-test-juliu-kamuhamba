<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Validation failure: { errors: { field: message } } with HTTP 422. */
    public static function invalid(array $errors): never
    {
        self::json(['errors' => $errors], 422);
    }

    /** Parsed JSON request body (empty array when missing or malformed). */
    public static function body(): array
    {
        $data = json_decode(file_get_contents('php://input') ?: '', true);
        return is_array($data) ? $data : [];
    }
}
