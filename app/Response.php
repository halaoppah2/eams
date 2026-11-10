<?php

namespace App;

class Response
{
    public static function html(
        string $content,
        int $status = 200
    ): void {
        http_response_code($status);

        header('Content-Type: text/html; charset=UTF-8');

        echo $content;
    }

    public static function json(
        mixed $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }

    public static function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }
}