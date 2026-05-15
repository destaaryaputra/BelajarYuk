<?php

namespace App\Middlewares;

use App\Utils\Response;
use App\Utils\Security;

/**
 * Authentication Middleware
 * Check user login status & permissions
 */

class AuthMiddleware {
    public static function checkAuth(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = $_SESSION['auth_token'] ?? self::getBearerToken();
        
        if (!$token) {
            return false;
        }

        $decoded = Security::verifyJWT($token);
        if (!$decoded) {
            unset($_SESSION['auth_token']);
            return false;
        }

        $_SESSION['auth_token'] = $token;
        $_SESSION['user'] = $decoded;
        return true;
    }

    private static function getBearerToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['Authorization']
            ?? null;

        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        }

        if (!$header || !preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    public static function requireAuth(): void {
        if (!self::checkAuth()) {
            Response::error('Unauthorized. Please login first.', null, 401);
        }
    }

    public static function getAuthUser(): ?array {
        if (self::checkAuth()) {
            return $_SESSION['user'] ?? null;
        }
        return null;
    }
}
