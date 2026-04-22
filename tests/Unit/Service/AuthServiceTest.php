<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\AuthService;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;

    protected function setUp(): void
    {
        $this->authService = new AuthService('test-secret-key-for-jwt-operations');
    }

    public function testHashPasswordCreatesDifferentHashesThanInput(): void
    {
        $password = 'SecurePassword123!';
        $hash = $this->authService->hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertStringContainsString('$2y$', $hash); // bcrypt hash format
        $this->assertGreaterThan(50, strlen($hash)); // bcrypt produces ~60 char hash
    }

    public function testHashPasswordWithSpecialCharacters(): void
    {
        $password = 'P@$$w0rd!&*()[]{}';
        $hash = $this->authService->hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertStringContainsString('$2y$', $hash);
    }

    public function testVerifyPasswordReturnsTrueForCorrectPassword(): void
    {
        $password = 'MyPassword456';
        $hash = $this->authService->hashPassword($password);

        $this->assertTrue($this->authService->verifyPassword($password, $hash));
    }

    public function testVerifyPasswordReturnsFalseForIncorrectPassword(): void
    {
        $password = 'MyPassword456';
        $wrongPassword = 'WrongPassword';
        $hash = $this->authService->hashPassword($password);

        $this->assertFalse($this->authService->verifyPassword($wrongPassword, $hash));
    }

    public function testVerifyPasswordReturnsFalseForEmptyPassword(): void
    {
        $password = 'MyPassword456';
        $hash = $this->authService->hashPassword($password);

        $this->assertFalse($this->authService->verifyPassword('', $hash));
    }

    public function testGenerateTokenReturnsValidJWTString(): void
    {
        $user = new User('test@example.com', 'hashedpassword', 'Test User');
        $token = $this->authService->generateToken($user);

        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token); // JWT has 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    public function testGenerateTokenIncludesUserIdAndEmail(): void
    {
        $user = new User('john@example.com', 'hashedpassword', 'John Doe');
        $token = $this->authService->generateToken($user);

        $decoded = $this->authService->validateToken($token);
        $this->assertEquals($user->getId(), $decoded->user_id);
        $this->assertEquals('john@example.com', $decoded->email);
    }

    public function testValidateTokenReturnsMissingUserIdException(): void
    {
        // Manually create a malformed token
        $malformedPayload = [
            'iss' => 'ai-assistant-api',
            'aud' => 'ai-assistant-client',
            'iat' => time(),
            'exp' => time() + 86400,
            // Missing user_id intentionally
        ];

        $token = \Firebase\JWT\JWT::encode($malformedPayload, 'test-secret-key-for-jwt-operations', 'HS256');

        // Try to get user ID from token without user_id field
        $this->expectException(\Exception::class);
        $this->authService->getUserIdFromToken($token);
    }

    public function testValidateTokenThrowsExceptionForExpiredToken(): void
    {
        $expiredPayload = [
            'iss' => 'ai-assistant-api',
            'aud' => 'ai-assistant-client',
            'iat' => time() - 86400, // 1 day ago
            'exp' => time() - 1, // Expired 1 second ago
            'user_id' => 'test-user-id',
            'email' => 'test@example.com',
        ];

        $token = \Firebase\JWT\JWT::encode($expiredPayload, 'test-secret-key-for-jwt-operations', 'HS256');

        $this->expectException(\Exception::class);
        $this->authService->validateToken($token);
    }

    public function testValidateTokenThrowsExceptionForInvalidSignature(): void
    {
        $user = new User('test@example.com', 'hashedpassword', 'Test User');
        $token = $this->authService->generateToken($user);

        // Create a new AuthService with different secret
        $differentAuthService = new AuthService('different-secret-key');

        $this->expectException(\Exception::class);
        $differentAuthService->validateToken($token);
    }

    public function testIsTokenValidReturnsTrueForValidToken(): void
    {
        $user = new User('test@example.com', 'hashedpassword', 'Test User');
        $token = $this->authService->generateToken($user);

        $this->assertTrue($this->authService->isTokenValid($token));
    }

    public function testIsTokenValidReturnsFalseForExpiredToken(): void
    {
        $expiredPayload = [
            'iss' => 'ai-assistant-api',
            'aud' => 'ai-assistant-client',
            'iat' => time() - 86400,
            'exp' => time() - 1,
            'user_id' => 'test-user-id',
            'email' => 'test@example.com',
        ];

        $token = \Firebase\JWT\JWT::encode($expiredPayload, 'test-secret-key-for-jwt-operations', 'HS256');

        $this->assertFalse($this->authService->isTokenValid($token));
    }

    public function testIsTokenValidReturnsFalseForInvalidSignature(): void
    {
        $user = new User('test@example.com', 'hashedpassword', 'Test User');
        $token = $this->authService->generateToken($user);

        $differentAuthService = new AuthService('different-secret-key');

        $this->assertFalse($differentAuthService->isTokenValid($token));
    }

    public function testGetUserIdFromTokenReturnsCorrectUserId(): void
    {
        $user = new User('test@example.com', 'hashedpassword', 'Test User');
        $token = $this->authService->generateToken($user);

        $userId = $this->authService->getUserIdFromToken($token);
        $this->assertEquals($user->getId(), $userId);
    }

    public function testConstructorThrowsExceptionForEmptySecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AuthService('');
    }
}
