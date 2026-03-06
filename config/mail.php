<?php

return [
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Spark')
    ],
    'reply' => [
        'address' => env('MAIL_REPLY_ADDRESS', 'reply@example.com'),
        'name' => env('MAIL_REPLY_NAME', 'Spark')
    ],
    'smtp' => [
        'enabled' => env('MAIL_SMTP', false),
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => env('MAIL_PORT', 2525),
        'username' => env('MAIL_USERNAME', null),
        'password' => env('MAIL_PASSWORD', null),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    ],
];