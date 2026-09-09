<?php

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run composer install before running tests.\n");
    exit(2);
}
require $autoload;

// Applied before application creation; .env and compiled config stay untouched.
$_ENV['APP_ENV'] = 'testing';
