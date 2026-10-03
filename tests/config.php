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
    'storage' => [
        'default' => 'local',
        'disks' => [
            'local' => ['root' => "$storage/app/private"],
            'public' => ['root' => "$storage/app/public"],
        ],
    ],
    'cache' => [
        'driver' => 'file',
        'connections' => ['file' => ['path' => "$storage/cache", 'lock_path' => "$storage/locks"]],
    ],
    'queue' => [
        'driver' => 'file',
        'connections' => ['file' => ['path' => "$storage/queue"]],
    ],
    'session' => [
        'handler' => 'file',
        'connections' => ['file' => ['path' => "$storage/sessions"]],
    ],
];
