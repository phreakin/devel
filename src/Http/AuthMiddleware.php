<?php

declare(strict_types=1);

namespace App\Http;

use App\Service\AuthService;

class AuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Extract and validate Bearer token from request
     * Returns user ID if valid, throws exception if invalid
     */
    public function authenticate(Request $request): string
    {
        $authHeader = $request->getHeader('Authorization', '');

        if (empty($authHeader)) {
            throw new \Exception('Missing Authorization header');
        }

        // Extract Bearer token
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            throw new \Exception('Invalid Authorization header format');
        }

        $token = $matches[1];

        if (!$this->authService->isTokenValid($token)) {
            throw new \Exception('Invalid or expired token');
        }

        return $this->authService->getUserIdFromToken($token);
    }

    /**
     * Check if request has valid token without throwing
     */
    public function hasValidToken(Request $request): bool
    {
        try {
            $authHeader = $request->getHeader('Authorization', '');
            if (empty($authHeader)) {
                return false;
            }

            if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return false;
            }

            return $this->authService->isTokenValid($matches[1]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get token from request without validation
     */
    public function getToken(Request $request): ?string
    {
        $authHeader = $request->getHeader('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
