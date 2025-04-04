<?php

/**
 * This file contains the environment settings for debugging, directories, API keys, database connections, and mail server configurations.
 * Each configuration key is associated with a value that can be used to customize the application.
 *
 * @return array
 *     An associative array of settings.
 */

return [
    // Debugging settings
    'debug' => true,

    // Directory paths
    'storage_dir' => __DIR__ . '/storage', // Storage directory
    'cache_dir' => __DIR__ . '/storage/cache', // Cache files directory
    'upload_dir' => __DIR__ . '/public/uploads', // Upload directory
    'views_dir' => __DIR__ . '/resources/views', // Template directory
    'lang_dir' => __DIR__ . '/languages', // Language files directory

    // URL settings
    'media_url' => '/uploads/', // Media URL
    'asset_url' => '/assets/', // Asset URL

    // Localization settings
    'lang' => 'en', // Default language

    // Security settings
    'app_key' => '{APP_KEY}', // Application key for encryption

    // Database connection settings
    'database' => [
        'driver' => 'sqlite', // Database driver
        'file' => __DIR__ . '/database/sqlite.db', // SQLite Database filepath 
    ],

    // Mail server configurations
    'mail' => [
        'mailer' => [
            'address' => '{MAILER_ADDRESS}',
            'name' => '{MAILER_NAME}'
        ],
        'reply' => [
            'address' => '{REPLY_ADDRESS}',
            'name' => '{REPLY_NAME}'
        ],
        'smtp' => [
            'enabled' => false,
            'host' => '{SMTP_HOST}',
            'port' => 2525,
            'username' => '{SMTP_USERNAME}',
            'password' => '{SMTP_PASSWORD}',
            'encryption' => 'tls|ssl',
        ],
    ],
];
