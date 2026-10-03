<?php

return [
    // Default database connection name
    'default' => env('DB_CONNECTION', 'sqlite'),

    // Database connections for different drivers
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'file' => dirname(__DIR__) . '/database/sqlite.db', // SQLite Database filepath 
        ],
        'default' => [
            // 'driver' => 'mysql', // auto detected from env('DB_CONNECTION')
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'name' => env('DB_DATABASE', 'spark'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
];