<?php

return [
    'driver' => env('QUEUE_CONNECTION', 'database'),
    'connections' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/queue.d',
        ],
        'redis' => [
            'driver' => 'redis',
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