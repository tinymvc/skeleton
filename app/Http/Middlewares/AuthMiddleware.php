<?php

namespace App\Http\Middlewares;

use Spark\Foundation\Http\Middlewares\AuthMiddleware as Middleware;
use Spark\Http\Request;

class AuthMiddleware extends Middleware
{
    protected function failed(Request $request, array $guards): mixed
    {
        abort(401, 'Unauthenticated.');

        return null; // This line will never be reached, but is added to satisfy the return type.
    }
}