<?php

declare(strict_types=1);

// Load environment variables
require __DIR__ . '/../vendor/autoload.php';

// Load .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get Doctrine EntityManager
$entityManager = require __DIR__ . '/../config/doctrine.php';

// Get OpenAI API key from environment
$openaiApiKey = $_ENV['OPENAI_API_KEY'] ?? null;

// Get JWT secret from environment
$jwtSecret = $_ENV['JWT_SECRET'] ?? null;

// Create and handle API request
$request = \App\Http\Request::fromGlobal();
$api = new \App\ApiApplication($entityManager, $openaiApiKey, $jwtSecret);

try {
    $response = $api->handleRequest($request);
    http_response_code($response->getStatusCode());
    echo $response->toJson();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null,
    ]);
}
