<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

// Get environment variables
$dbDriver = $_ENV['DB_DRIVER'] ?? 'pdo_mysql';
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'devel_db';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

// Doctrine configuration
$isDevMode = ($_ENV['APP_ENV'] ?? 'production') === 'development';
$config = ORMSetup::createAttributeMetadataConfiguration(
    paths: [__DIR__ . '/src/Entity'],
    isDevMode: $isDevMode,
);

// Database connection
$connection = DriverManager::getConnection([
    'driver' => $dbDriver,
    'host' => $dbHost,
    'port' => $dbPort,
    'dbname' => $dbName,
    'user' => $dbUser,
    'password' => $dbPass,
], $config);

// Create EntityManager
$entityManager = new EntityManager($connection, $config);

return $entityManager;
