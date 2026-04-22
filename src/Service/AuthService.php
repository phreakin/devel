<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Entity\User;

class AuthService
{
    private string $jwtSecret;
    private string $jwtAlgorithm = 'HS256';
    private int $jwtExpiration = 86400; // 24 hours

    public function __construct(string $jwtSecret)
    {
        if (empty($jwtSecret)) {
            throw new \InvalidArgumentException('JWT secret cannot be empty');
        }
        $this->jwtSecret = $jwtSecret;
    }

    /**
     * Hash a password for secure storage
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);
    }

    /**
     * Verify a password against a hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate JWT token for a user
     */
    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => 'ai-assistant-api',
            'aud' => 'ai-assistant-client',
            'iat' => time(),
            'exp' => time() + $this->jwtExpiration,
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    /**
     * Decode and validate JWT token
     * Returns decoded token payload on success
     * Throws exception on failure
     */
    public function validateToken(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
        } catch (\Exception $e) {
            throw new \Exception("Invalid token: " . $e->getMessage());
        }
    }

    /**
     * Extract user ID from token
     */
    public function getUserIdFromToken(string $token): string
    {
        try {
            $decoded = $this->validateToken($token);
            return $decoded->user_id;
        } catch (\Exception $e) {
            throw new \Exception("Failed to extract user ID from token: " . $e->getMessage());
        }
    }

    /**
     * Check if token is still valid
     */
    public function isTokenValid(string $token): bool
    {
        try {
            $this->validateToken($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
