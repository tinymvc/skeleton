<?php

return [
    'default' => env('FILESYSTEM_DISK', env('FILESYSTEM_DRIVER', 'local')),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => dirname(__DIR__) . '/storage/app',
            'visibility' => 'private',
        ],
        'public' => [
            'driver' => 'local',
            'root' => dirname(__DIR__) . '/storage/uploads',
            'url' => rtrim(env('APP_URL', 'http://localhost:8080'), '/') . '/uploads',
            'visibility' => 'public',
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'token' => env('AWS_SESSION_TOKEN'), // Optional temporary credentials
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'), // Optional public/CDN URL; never used for signing
            'endpoint' => env('AWS_ENDPOINT'), // Null for AWS; regional origin for Spaces/other services
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'acl' => null, // Omit ACLs for policy-controlled buckets; 'public-read' only if supported
            'timeout' => 300,
            'list_version' => 2, // Use 1 only for a provider that requires legacy ListObjects
        ],
    ],
];
