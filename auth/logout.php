<?php
/**
 * TTS PMS - Logout Handler
 * Handles user logout and session cleanup
 */

// Start session
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Clear any cookies if they exist
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Redirect to main sign-in page
header('Location: sign-in.php');
exit;
?>
