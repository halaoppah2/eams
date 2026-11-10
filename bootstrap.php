<?php

date_default_timezone_set('Africa/Accra');

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Session;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/eams/public';
$appUrlParts = parse_url($appUrl);

if (
    $appUrlParts === false
    || !isset($appUrlParts['scheme'], $appUrlParts['host'])
    || !in_array(strtolower($appUrlParts['scheme']), ['http', 'https'], true)
) {
    throw new \RuntimeException(
        'APP_URL must be an absolute HTTP or HTTPS URL.'
    );
}

$appBasePath = rtrim($appUrlParts['path'] ?? '', '/');

if ($appBasePath === '/') {
    $appBasePath = '';
}

if (!defined('APP_BASE_PATH')) {
    define('APP_BASE_PATH', $appBasePath);
}

Session::start();