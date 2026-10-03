<?php

namespace Tests;

use Spark\Foundation\Application;

/** Base test case for all tests. */
abstract class TestCase extends \Spark\Testing\ApplicationTestCase
{
    protected function testStorageDirectory(): string
    {
        return dirname(__DIR__) . '/storage/framework/testing';
    }

    protected function createApplication(): Application
    {
        /** @var Application $app */
        $app = require dirname(__DIR__) . '/bootstrap/app.php';
        $app->mergeConfig(require __DIR__ . '/config.php');

        return $app;
    }
}
