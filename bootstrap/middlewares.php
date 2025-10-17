<?php

/**
 * Middleware configuration.
 *
 * This file returns an array of middleware classes used by the application.
 * Each middleware is associated with a key that can be used to reference it.
 * 
 * @return array
 *   An associative array of middleware keys and their corresponding class names.
 */
return [
    'csrf' => \App\Http\Middlewares\CsrfProtectionMiddleware::class,
    'cors' => \App\Http\Middlewares\CorsControlMiddleware::class,
    'throttle' => \App\Http\Middlewares\ThrottleMiddleware::class,
];
