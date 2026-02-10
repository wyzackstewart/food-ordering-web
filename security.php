<?php
class Security {
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        // Remove whitespace
        $input = trim($input);
        
        // Remove slashes if magic quotes are enabled
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone) {
        // Remove all non-digit characters except plus sign
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $cleaned);
    }
    
    public static function validatePassword($password) {
        // At least 6 characters, one uppercase, one lowercase, one number
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/', $password);
    }
    
    public static function escapeSql($db, $string) {
        return $db->real_escape_string($string);
    }
    
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function preventXSS($string) {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1); // Use only with HTTPS
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }
}
?>