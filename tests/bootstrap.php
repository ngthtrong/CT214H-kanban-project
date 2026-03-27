<?php
/**
 * PHPUnit Bootstrap File
 * 
 * This file is executed before running any test.
 * Set up the test environment here.
 */

// Define project root
define('PROJECT_ROOT', dirname(__DIR__));
define('TESTS_DIR', __DIR__);

// Load Composer autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Load environment variables (if using .env)
if (file_exists(PROJECT_ROOT . '/.env.testing')) {
    $env_vars = parse_ini_file(PROJECT_ROOT . '/.env.testing');
    foreach ($env_vars as $key => $value) {
        putenv("$key=$value");
    }
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Database setup for testing (customize as needed)
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_DATABASE', getenv('DB_DATABASE') ?: 'kanban_test');
    define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
}
