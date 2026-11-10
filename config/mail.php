<?php

return [
    'host' => $_ENV['MAIL_HOST'] ?? '',
    'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'encryption' => strtolower($_ENV['MAIL_ENCRYPTION'] ?? 'tls'),
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'EAMS',
    'app_url' => rtrim(
        $_ENV['APP_URL'] ?? 'http://localhost/eams/public',
        '/'
    ),
];
