<?php
/**
 * TTS PMS - Dashboard Router
 * Routes users to appropriate dashboard based on their role
 */

require_once '../config/init.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../auth/sign-in.php');
    exit;
}

// Route to appropriate dashboard based on role
$role = $_SESSION['role'];

switch ($role) {
    case 'visitor':
        header('Location: visitor/');
        break;
    case 'candidate':
        header('Location: candidate/');
        break;
    case 'employee':
        header('Location: employee/');
        break;
    case 'manager':
        header('Location: manager/');
        break;
    case 'ceo':
        header('Location: ceo/');
        break;
    case 'admin':
        header('Location: ../admin/');
        break;
    default:
        // Unknown role, redirect to login
        session_destroy();
        header('Location: ../auth/sign-in.php?error=invalid_role');
        break;
}
exit;
?>
