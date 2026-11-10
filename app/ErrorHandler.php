<?php

namespace App;

use RuntimeException;
use Throwable;

class ErrorHandler
{
    public static function render(Throwable $error): string
    {
        if ($error instanceof HttpException) {
            $statusCode = $error->statusCode();
            $title = $statusCode >= 500
                ? 'Something went wrong'
                : 'Unable to complete your request';
            $message = $error->getMessage();
        } elseif ($error instanceof RuntimeException) {
            $statusCode = 422;
            $title = 'Unable to complete your request';
            $message = $error->getMessage();
        } else {
            $statusCode = 500;
            $title = 'Something went wrong';
            $message = 'An unexpected error occurred. Please try again later.';

            error_log(sprintf(
                'Unhandled application error: %s in %s:%d',
                $error->getMessage(),
                $error->getFile(),
                $error->getLine()
            ));
        }

        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');

        ob_start();
        require __DIR__ . '/Views/errors/show.php';

        return (string) ob_get_clean();
    }
}
