<?php
// Secure session initialization
session_start([
    'cookie_lifetime' => 86400, // 24 hours
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'use_trans_sid' => false
]);

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>