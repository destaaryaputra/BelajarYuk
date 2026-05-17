<?php

namespace App\Utils;

use DOMDocument;
use DOMElement;
use DOMNode;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Security Utility Class
 * Fungsi-fungsi keamanan untuk: hashing, validation, sanitization
 */

class Security {
    /**
     * Hash password menggunakan bcrypt
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password dengan hash
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input string (prevent XSS)
     */
    public static function sanitize(string $input): string {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize rich-text HTML (basic allowlist)
     */
    public static function sanitizeHtml(?string $html): string {
        if ($html === null || $html === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            return strip_tags($html);
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ol', 'ul', 'li', 'a', 'blockquote', 'code', 'pre', 'h2', 'h3', 'h4', 'h5', 'h6', 'img'];
        $allowedAttrs = ['href', 'src', 'alt', 'title', 'target', 'rel'];

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        // Load with UTF-8 prefix to handle Indonesian/Special characters correctly
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        self::sanitizeNode($doc, $allowedTags, $allowedAttrs);

        $clean = $doc->saveHTML();
        return $clean === null ? '' : $clean;
    }

    private static function sanitizeNode(DOMNode $node, array $allowedTags, array $allowedAttrs): void {
        if ($node instanceof DOMElement) {
            $element = $node;
            $tag = strtolower($element->nodeName);
            if (!in_array($tag, $allowedTags, true)) {
                $parent = $element->parentNode;
                if ($parent) {
                    while ($element->firstChild) {
                        $parent->insertBefore($element->firstChild, $element);
                    }
                    $parent->removeChild($element);
                }
                return;
            }

            if ($element->hasAttributes()) {
                self::sanitizeAttributes($element, $tag, $allowedAttrs);
            }
        }

        if ($node->hasChildNodes()) {
            $children = [];
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }
            foreach ($children as $child) {
                self::sanitizeNode($child, $allowedTags, $allowedAttrs);
            }
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag, array $allowedAttrs): void {
        $attrNames = [];
        foreach ($element->attributes as $attr) {
            $attrNames[] = $attr->name;
        }

        foreach ($attrNames as $attrName) {
            $attrLower = strtolower($attrName);
            if (strpos($attrLower, 'on') === 0 || !in_array($attrLower, $allowedAttrs, true)) {
                $element->removeAttribute($attrName);
                continue;
            }

            $value = $element->getAttribute($attrName);
            if (in_array($attrLower, ['href', 'src'], true)) {
                if (!self::isSafeUrl($value)) {
                    $element->removeAttribute($attrName);
                } elseif ($tag === 'a') {
                    $element->setAttribute('rel', 'noopener noreferrer');
                    $element->setAttribute('target', '_blank');
                }
            }
        }
    }

    private static function isSafeUrl(string $url): bool {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return false;
        }

        if (strpos($trimmed, '#') === 0 || strpos($trimmed, '/') === 0) {
            return true;
        }

        $parts = parse_url($trimmed);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Validate email format
     */
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                error_log('CSRF: failed to start session while generating token.');
                return '';
            }
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken(string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                error_log('CSRF: failed to start session while verifying token.');
                return false;
            }
        }
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        $cookieToken = $_COOKIE['csrf_token'] ?? null;
        
        // Match against session if available, otherwise fallback to cookie (Double Submit)
        if ($sessionToken && hash_equals($sessionToken, $token)) {
            return true;
        }
        
        if ($cookieToken && hash_equals($cookieToken, $token)) {
            return true;
        }

        error_log('CSRF verification failed: token mismatch.');
        return false;
    }

    /**
     * Generate JWT token using Firebase JWT library
     */
    public static function generateJWT(array $payload): string {
        $payload['iat'] = time();
        $payload['exp'] = time() + (defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600);
        
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    /**
     * Verify dan decode JWT token using Firebase JWT library
     */
    public static function verifyJWT(string $token): ?array {
        try {
            $decoded = JWT::decode(
                $token, 
                new Key(JWT_SECRET, 'HS256')
            );
            return (array) $decoded;
        } catch (Exception $e) {
            error_log("JWT Verification Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate random string
     */
    public static function generateRandomString(int $length = 32): string {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename(string $filename): string {
        return preg_replace("/[^a-zA-Z0-9._-]/", "", $filename);
    }
}
