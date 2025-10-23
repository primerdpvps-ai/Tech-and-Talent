<?php
/**
 * TTS PMS - Admin Logout
 * Handles admin session termination
 */

// Start session
session_start();

// Log logout if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once '../config/init.php';
    
    log_message('info', 'Admin logout', [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'] ?? 'unknown',
        'session_duration' => isset($_SESSION['login_time']) ? (time() - $_SESSION['login_time']) : 0,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
