<?php

namespace Tests;

use Spark\Foundation\Application;

abstract class TestCase extends \Spark\Testing\ApplicationTestCase
{
    protected function createApplication(): Application
    {
        return require dirname(__DIR__) . '/bootstrap/app.php';
    }
}
