<?php

return [
    'handler' => env('SESSION_HANDLER', 'database'),
    'connections' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_SESSION_CONNECTION'),
            'table' => env('DB_SESSION_TABLE', 'sessions'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/sessions',
        ],
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_DATABASE', 0),
            'prefix' => env('REDIS_PREFIX', 'spark'),
            'timeout' => env('REDIS_TIMEOUT', 0.0),
            'read_timeout' => env('REDIS_READ_TIMEOUT', 0.0),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],
    ],
];