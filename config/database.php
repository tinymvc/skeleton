<?php

return [
    'driver' => env('DB_CONNECTION', 'sqlite'), // Database driver
    'connections' => [
        'sqlite' => [
            'file' => dirname(__DIR__) . '/database/sqlite.db', // SQLite Database filepath 
        ],
        'default' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'name' => env('DB_DATABASE', 'spark'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
        ],
    ],
];