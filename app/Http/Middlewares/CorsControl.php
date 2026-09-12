<?php

namespace App\Http\Middlewares;

use Spark\Foundation\Http\Middlewares\CorsAccessControl as CORS;

/**
 * Cross-Origin Resource Sharing (CORS) control.
 * 
 * This middleware handles the CORS headers for the application and allows 
 * you to specify which origins, methods, and headers are allowed for 
 * cross-origin requests.
 * 
 */
class CorsControl extends CORS
{
    public function __construct()
    {
        $this->config = config('cors');
    }
}