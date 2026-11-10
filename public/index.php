<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Router;
use App\ErrorHandler;

$initialBufferLevel = ob_get_level();
ob_start();

try {
    $router = new Router();

    require_once __DIR__ . '/../routes/web.php';

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = is_string($uri) ? $uri : '/';

    $basePath = APP_BASE_PATH;

    if (
        $basePath !== ''
        && ($uri === $basePath || str_starts_with($uri, $basePath . '/'))
    ) {
        $uri = substr($uri, strlen($basePath));
    }

    $uri = '/' . trim($uri, '/');

    if ($uri !== '/' && $uri === '/index.php') {
        $uri = '/';
    }

    $response = $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $uri
    );

    echo $response;
    ob_end_flush();
} catch (Throwable $error) {
    while (ob_get_level() > $initialBufferLevel) {
        ob_end_clean();
    }

    echo ErrorHandler::render($error);
}
