<?php

namespace App\Providers;

use Spark\Container;
use Spark\Contracts\ServiceProvider;

/**
 * This file contains the service provider for cli application.
 * 
 * @package App\Providers
 */
class ConsoleServiceProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // i am registering services
    }

    public function boot(): void
    {
        // i am bootstrapping services
    }
}