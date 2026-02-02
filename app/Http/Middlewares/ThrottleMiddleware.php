<?php

namespace App\Http\Middlewares;

use Spark\Foundation\Http\Middlewares\ThrottleIncomingRequests as Middleware;

/**
 * Middleware to throttle incoming requests.
 *
 * This middleware extends the base ThrottleIncomingRequests middleware
 * provided by the Spark framework to limit the rate of incoming requests
 * to the application, helping to prevent abuse and ensure fair usage.
 */
class ThrottleMiddleware extends Middleware
{
    /** @var array $config Configuration array for throttling settings. */
    protected array $config = [];
}
