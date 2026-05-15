<?php

namespace App\Middlewares;

use App\Utils\Response;
use App\Utils\Security;

/**
 * CSRF Protection Middleware
 * Proteksi terhadap Cross-Site Request Forgery attacks
 */

class CSRFMiddleware {
    public static function generateToken(): string {
        return Security::generateCSRFToken();
    }

    public static function verify(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!$token || !Security::verifyCSRFToken($token)) {
                error_log("CSRF verification failed. Token provided: " . ($token ? 'YES' : 'NO') . ", Session token set: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO'));
                Response::error('CSRF token mismatch.', null, 403);
            }
        }
    }
}
