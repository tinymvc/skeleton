<?php

// Each feature test receives its own temporary directory from Spark\Testing\ApplicationTestCase.
$storage = env('TEST_STORAGE_PATH');
if (!$storage) {
    throw new LogicException('Use the application TestCase to boot feature tests.');
}

return [
    'app' => [
        'debug' => false,
        'key' => '70e313f57a932c7388cae00b80a11912',
        'timezone' => 'UTC',
        'url' => 'http://localhost:8080',
        'storage_dir' => $storage,
        'temp_dir' => "$storage/temp",
        'upload_dir' => "$storage/uploads",
    ],
    'database' => [
        'driver' => 'sqlite',
        'connections' => ['sqlite' => ['file' => ':memory:']],
    ],
    'cache' => [
        'driver' => 'sqlite',
        'connections' => ['sqlite' => ['path' => "$storage/cache"]],
    ],
    'queue' => [
        'driver' => 'sqlite',
        'connections' => ['sqlite' => ['path' => "$storage/queue/jobs.db"]],
    ],
];
