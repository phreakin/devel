<?php

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$entityManager = require __DIR__ . '/config/doctrine.php';

use Doctrine\ORM\Tools\SchemaTool;

$schemaTool = new SchemaTool($entityManager);
$classes = $entityManager->getMetadataFactory()->getAllMetadata();

try {
    // Create the schema
    $schemaTool->createSchema($classes);
    echo "Schema created successfully!\n";
} catch (\Exception $e) {
    echo "Error creating schema: " . $e->getMessage() . "\n";
    exit(1);
}
