<?php

return [
    'key' => env('APP_KEY'), // Application key for encryption

    'debug' => env('APP_DEBUG', true), // Enable or disable debug mode

    'name' => env('APP_NAME', 'TinyMVC'), // Application name
    'timezone' => env('APP_TIMEZONE', 'UTC'), // Application timezone
    'lang' => env('APP_LOCALE', 'en'), // Default language
    'root_url' => env('APP_URL', 'http://localhost:8080'), // Application URL

    // Directory paths
    'storage_dir' => dirname(__DIR__) . '/storage', // Storage directory
    'cache_dir' => dirname(__DIR__) . '/storage/cache', // Cache files directory
    'upload_dir' => dirname(__DIR__) . '/storage/uploads', // Upload directory
    'views_dir' => dirname(__DIR__) . '/resources/views', // Template directory
    'lang_dir' => dirname(__DIR__) . '/resources/languages', // Language files directory

    // URL settings
    'media_url' => '/uploads/', // Media URL
    'asset_url' => '/assets/', // Asset URL
];