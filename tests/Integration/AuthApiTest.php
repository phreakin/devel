<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\ApiApplication;
use App\Http\Request;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;

class AuthApiTest extends TestCase
{
    private EntityManager $entityManager;
    private ApiApplication $api;

    protected function setUp(): void
    {
        // Use in-memory SQLite for tests
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__ . '/../../src/Entity'],
            isDevMode: true,
        );

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $this->entityManager = new EntityManager($connection, $config);

        // Create schema
        $connection->executeStatement('CREATE TABLE users (
            id VARCHAR(36) PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            active BOOLEAN DEFAULT 1,
            createdAt DATETIME NOT NULL,
            updatedAt DATETIME NOT NULL
        )');

        $connection->executeStatement('CREATE TABLE conversations (
            id VARCHAR(36) PRIMARY KEY,
            userId VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            aiModel VARCHAR(100) DEFAULT "gpt-4",
            active BOOLEAN DEFAULT 1,
            createdAt DATETIME NOT NULL,
            updatedAt DATETIME NOT NULL,
            FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
        )');

        $connection->executeStatement('CREATE TABLE messages (
            id VARCHAR(36) PRIMARY KEY,
            conversationId VARCHAR(36) NOT NULL,
            role VARCHAR(50) NOT NULL,
            content TEXT NOT NULL,
            metadata TEXT,
            createdAt DATETIME NOT NULL,
            updatedAt DATETIME NOT NULL,
            FOREIGN KEY (conversationId) REFERENCES conversations(id) ON DELETE CASCADE
        )');
        
        // Generate a test JWT secret
        $jwtSecret = 'test-jwt-secret-' . time();
        
        $this->api = new ApiApplication(
            $this->entityManager,
            null, // No OpenAI key for auth tests
            $jwtSecret
        );
    }

    protected function tearDown(): void
    {
        // SQLite in-memory database is automatically cleaned up
    }

    public function testRegisterWithValidData(): void
    {
        $request = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'newuser@example.com',
                'password' => 'SecurePassword123',
                'name' => 'New User',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($request);

        $this->assertEquals(201, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('User registered successfully. Please login.', $body['message']);
        $this->assertNotEmpty($body['data']['userId']);
        $this->assertEquals('newuser@example.com', $body['data']['email']);
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        // Register first user
        $request1 = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'duplicate@example.com',
                'password' => 'SecurePassword123',
                'name' => 'First User',
            ]),
            ['Content-Type' => 'application/json']
        );
        $this->api->handleRequest($request1);

        // Try to register with same email
        $request2 = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'duplicate@example.com',
                'password' => 'DifferentPassword456',
                'name' => 'Second User',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($request2);

        $this->assertEquals(409, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Email already registered', $body['message']);
    }

    public function testRegisterWithMissingFields(): void
    {
        $request = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'incomplete@example.com',
                // Missing password and name
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($request);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Missing required fields', $body['message']);
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $request = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'not-an-email',
                'password' => 'SecurePassword123',
                'name' => 'Test User',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($request);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Invalid email format', $body['message']);
    }

    public function testRegisterWithShortPassword(): void
    {
        $request = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'user@example.com',
                'password' => 'Short1',
                'name' => 'Test User',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($request);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Password must be at least 8 characters', $body['message']);
    }

    public function testLoginWithValidCredentials(): void
    {
        // Register user first
        $registerRequest = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'login@example.com',
                'password' => 'MyPassword123',
                'name' => 'Login User',
            ]),
            ['Content-Type' => 'application/json']
        );
        $this->api->handleRequest($registerRequest);

        // Now login
        $loginRequest = new Request(
            'POST',
            '/api/auth/login',
            [],
            [],
            json_encode([
                'email' => 'login@example.com',
                'password' => 'MyPassword123',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($loginRequest);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Login successful', $body['message']);
        $this->assertNotEmpty($body['data']['token']);
        $this->assertNotEmpty($body['data']['userId']);
        $this->assertEquals('login@example.com', $body['data']['email']);
        $this->assertEquals('Login User', $body['data']['name']);
    }

    public function testLoginWithIncorrectPassword(): void
    {
        // Register user first
        $registerRequest = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'wrong@example.com',
                'password' => 'CorrectPassword123',
                'name' => 'Wrong Password User',
            ]),
            ['Content-Type' => 'application/json']
        );
        $this->api->handleRequest($registerRequest);

        // Try to login with wrong password
        $loginRequest = new Request(
            'POST',
            '/api/auth/login',
            [],
            [],
            json_encode([
                'email' => 'wrong@example.com',
                'password' => 'WrongPassword123',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($loginRequest);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertEquals('Invalid email or password', $body['message']);
    }

    public function testLoginWithNonexistentEmail(): void
    {
        $loginRequest = new Request(
            'POST',
            '/api/auth/login',
            [],
            [],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => 'SomePassword123',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($loginRequest);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Invalid email or password', $body['message']);
    }

    public function testLoginWithMissingFields(): void
    {
        $loginRequest = new Request(
            'POST',
            '/api/auth/login',
            [],
            [],
            json_encode([
                'email' => 'test@example.com',
                // Missing password
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($loginRequest);

        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertStringContainsString('Missing required fields', $body['message']);
    }

    public function testCreateConversationWithoutAuthToken(): void
    {
        $request = new Request(
            'POST',
            '/api/conversations',
            [],
            [],
            json_encode([
                'title' => 'Test Conversation',
                'aiModel' => 'gpt-4',
            ]),
            ['Content-Type' => 'application/json']
        );

        $response = $this->api->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Missing Authorization header', $body['message']);
    }

    public function testCreateConversationWithInvalidToken(): void
    {
        $request = new Request(
            'POST',
            '/api/conversations',
            [],
            [],
            json_encode([
                'title' => 'Test Conversation',
                'aiModel' => 'gpt-4',
            ]),
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer invalid-token-here',
            ]
        );

        $response = $this->api->handleRequest($request);

        $this->assertEquals(401, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertStringContainsString('Invalid or expired token', $body['message']);
    }

    public function testCreateConversationWithValidToken(): void
    {
        // Register and login first
        $registerRequest = new Request(
            'POST',
            '/api/auth/register',
            [],
            [],
            json_encode([
                'email' => 'conversation@example.com',
                'password' => 'ConversationPassword123',
                'name' => 'Conversation User',
            ]),
            ['Content-Type' => 'application/json']
        );
        $this->api->handleRequest($registerRequest);

        $loginRequest = new Request(
            'POST',
            '/api/auth/login',
            [],
            [],
            json_encode([
                'email' => 'conversation@example.com',
                'password' => 'ConversationPassword123',
            ]),
            ['Content-Type' => 'application/json']
        );

        $loginResponse = $this->api->handleRequest($loginRequest);
        $loginBody = json_decode($loginResponse->getBody(), true);
        $token = $loginBody['data']['token'];

        // Create conversation with token
        $conversationRequest = new Request(
            'POST',
            '/api/conversations',
            [],
            [],
            json_encode([
                'title' => 'My First Conversation',
                'aiModel' => 'gpt-4',
                'description' => 'Test conversation with auth',
            ]),
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]
        );

        $response = $this->api->handleRequest($conversationRequest);

        $this->assertEquals(201, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Conversation created successfully', $body['message']);
        $this->assertEquals('My First Conversation', $body['data']['title']);
        $this->assertEquals('gpt-4', $body['data']['aiModel']);
    }
}
