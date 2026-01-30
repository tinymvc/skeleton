<?php

namespace App\Providers;

use Spark\Foundation\Providers\ServiceProvider;

/**
 * This file contains the service providers for the web application.
 * 
 * @package App\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // i am registering services
    }

    public function boot(): void
    {
        // i am bootstrapping services
    }
}