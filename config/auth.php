<?php

return [
    'guard' => env('DEFAULT_AUTH_GUARD', 'default'),
    'guards' => [
        'api' => [
            'jwt_expire' => '3 months',
            'jwt_token_table' => 'jwt_access_tokens', // Table name for storing JWT tokens if needed
            'channels' => ['session'],
        ],
        'session' => [
            'session_key' => 'user_id',
            'login_route' => 'login',
            'redirect_route' => 'dashboard',
            'cookie_enabled' => true,
            'cookie_name' => 'auth',
            'cookie_expire' => '6 months',
            'channels' => ['session'],
            'use_remember_token' => true, // Validate remember tokens from cookies
        ],
        'default' => [
            'session_key' => 'user_id',
            'cache_enabled' => false,
            'cache_name' => 'auth_cache',
            'cache_expire' => '10 minutes',
            'login_route' => 'login',
            'redirect_route' => 'dashboard',
            'cookie_enabled' => true,
            'cookie_name' => 'auth',
            'cookie_expire' => '6 months',
            'jwt_expire' => '3 months',
            'jwt_token_table' => 'jwt_access_tokens', // Table name for storing JWT tokens if needed
            'channels' => ['session', 'jwt', 'basic'],
            'use_remember_token' => true, // Validate remember tokens from cookies
        ],
    ],
];