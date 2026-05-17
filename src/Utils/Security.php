<?php

namespace App\Utils;

use DOMDocument;
use DOMElement;
use DOMNode;

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
    public static function sanitizeHtml(string $html): string {
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
        if ($node->nodeType === XML_ELEMENT_NODE && $node instanceof DOMElement) {
            $tag = strtolower($node->nodeName);
            if (!in_array($tag, $allowedTags, true)) {
                $parent = $node->parentNode;
                if ($parent) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                }
                return;
            }

            if ($node->hasAttributes()) {
                $attrNames = [];
                foreach ($node->attributes as $attr) {
                    $attrNames[] = $attr->name;
                }
                foreach ($attrNames as $attrName) {
                    $attrLower = strtolower($attrName);
                    if (strpos($attrLower, 'on') === 0 || !in_array($attrLower, $allowedAttrs, true)) {
                        $node->removeAttribute($attrName);
                        continue;
                    }

                    $value = $node->getAttribute($attrName);
                    if (in_array($attrLower, ['href', 'src'], true)) {
                        if (!self::isSafeUrl($value)) {
                            $node->removeAttribute($attrName);
                        } elseif ($tag === 'a') {
                            $node->setAttribute('rel', 'noopener noreferrer');
                            $node->setAttribute('target', '_blank');
                        }
                    }
                }
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
            @session_start();
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
            @session_start();
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

        error_log("CSRF Failure: Provided token doesn't match session ($sessionToken) or cookie ($cookieToken)");
        return false;
    }

    /**
     * Generate JWT token using Firebase JWT library
     */
    public static function generateJWT(array $payload): string {
        $payload['iat'] = time();
        $payload['exp'] = time() + (defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600);
        
        return \Firebase\JWT\JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    /**
     * Verify dan decode JWT token using Firebase JWT library
     */
    public static function verifyJWT(string $token): ?array {
        try {
            $decoded = \Firebase\JWT\JWT::decode(
                $token, 
                new \Firebase\JWT\Key(JWT_SECRET, 'HS256')
            );
            return (array) $decoded;
        } catch (\Exception $e) {
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
