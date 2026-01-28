<?php

namespace App\Providers;

use Spark\Foundation\Providers\ServiceProvider;

/**
 * This file contains the service provider for cli application.
 * 
 * @package App\Providers
 */
class ConsoleServiceProvider extends ServiceProvider
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