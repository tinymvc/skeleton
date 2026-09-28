<?php

return [
    'key' => env('APP_KEY'), // Application key for encryption
    'debug' => env('APP_DEBUG', true), // Enable or disable debug mode

    'name' => env('APP_NAME', 'Spark'), // Application name
    'timezone' => env('APP_TIMEZONE', 'UTC'), // Application timezone
    'lang' => env('APP_LOCALE', 'en'), // Default language
    'locale' => env('APP_LOCALE', 'en'), // Default language
    'url' => env('APP_URL', 'http://localhost:8080'), // Application URL

    // Directory paths
    'storage_dir' => dirname(__DIR__) . '/storage', // Storage directory
    'temp_dir' => dirname(__DIR__) . '/storage/temp', // Temporary files directory
    'upload_dir' => dirname(__DIR__) . '/storage/uploads', // Upload directory
    'views_dir' => dirname(__DIR__) . '/resources/views', // Template directory
    'lang_dir' => dirname(__DIR__) . '/resources/languages', // Language files directory

    // URL settings
    'media_url' => '/uploads/', // Media URL
    'asset_url' => '/assets/', // Asset URL

    // Trusted proxy IPs/CIDRs, e.g. ['10.0.0.0/8', '172.16.0.0/12', '127.0.0.1'].
    // Use '*' only if your load balancer always overwrites X-Forwarded-For.
    'trusted_proxies' => array_filter(
        array_unique(
            explode(',', env('TRUSTED_PROXIES', ''))
        )
    ),
];