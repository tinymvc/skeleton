<?php
/**
 * Entry point for the application.
 *
 * This file is the entry point for the entire application. It sets up
 * the application environment, loads the Composer autoloader, and
 * runs the bootstrap process.
 * 
 * @return void
 */

define('APP_START', microtime(true));

// Check if the Composer autoloader exists; if not, prompt the user to run composer install.
if (!is_file($autoload = dirname(__DIR__) . '/vendor/autoload.php')) {
    die("Run composer install before running the application.");
}

require $autoload; // Load the Composer autoloader for the application

/**
 * Runs the bootstrap process.
 *
 * This function is responsible for loading the application's
 * bootstrap file, which sets up the application environment and
 * runs the application.
 *
 * @return void
 */
(require dirname(__DIR__) . '/bootstrap/app.php')
    ->run();
