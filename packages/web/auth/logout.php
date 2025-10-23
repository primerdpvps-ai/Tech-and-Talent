<?php
/**
 * TTS PMS - User Logout
 * Handles user session termination and cleanup
 */

// Load configuration
require_once '../../../config/init.php';

// Start session
session_start();

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    if (function_exists('log_message')) {
        log_message('info', 'User logged out', [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'] ?? 'unknown',
            'role' => $_SESSION['role'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}

// Clear all session variables
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

// Destroy the session
session_destroy();

// Redirect to sign-in page with logout confirmation
header('Location: sign-in.php?logout=success');
exit;
?>
