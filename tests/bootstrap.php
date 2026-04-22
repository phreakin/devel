<?php

declare(strict_types=1);

// Bootstrap test environment
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load Composer autoloader
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloader)) {
    echo "Composer dependencies not installed. Run: composer install\n";
    exit(1);
}

require $autoloader;
