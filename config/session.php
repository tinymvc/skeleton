<?php

return [
    'handler' => env('SESSION_HANDLER', 'database'),

    // Minutes of inactivity before a session expires (used for gc, cookie
    // lifetime, redis TTL, and db/file expiry checks).
    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    // If true, the cookie is session-only and dies when the browser closes.
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    // The session storage connections for each of the supported session handlers.
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

    // The name of the cookie used to identify a session instance by ID.
    'cookie_name' => env('SESSION_COOKIE', 'spark_session'),
    'cookie_settings' => [
        'path' => env('SESSION_COOKIE_PATH', '/'),
        'domain' => env('SESSION_COOKIE_DOMAIN', ''),
        'secure' => env('SESSION_COOKIE_SECURE', null), // null = auto-detect HTTPS
        'http_only' => env('SESSION_COOKIE_HTTP_ONLY', true),
        'same_site' => env('SESSION_COOKIE_SAME_SITE', 'Lax'),
    ],

    // Native PHP gc lottery — chance gc runs on a given request (1/100 by default).
    'gc_probability' => (int) env('SESSION_GC_PROBABILITY', 1),
    'gc_divisor' => (int) env('SESSION_GC_DIVISOR', 100),
];