<?php

return [
    'driver' => env('CACHE_DRIVER', 'database'),
    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => env('DB_CACHE_TABLE', 'caches'),
            'connection' => env('DB_CACHE_CONNECTION'),
            'lock_connection' => env('DB_LOCK_CONNECTION'),
            'lock_table' => env('DB_LOCK_TABLE', 'locks'),
        ],
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/temp/cache',
            'lock_path' => dirname(__DIR__) . '/storage/framework/temp/locks',
            'file_mode' => 0664,
            'dir_mode' => 0775,
            'fsync' => false,          // true = survive power loss, slower
            'guard_timeout' => 5.0,    // max seconds to wait for a shard guard
            'gc_interval' => 300,      // throttle for has()/retrieve($eraseExpired) sweeps
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