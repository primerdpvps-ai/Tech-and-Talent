<?php
/**
 * TTS PMS - Session Cleanup Script
 * Clears all sessions and cookies to resolve redirect loops
 */

// Start session
session_start();

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', true, true);
}

// Destroy session
session_destroy();

// Output cleanup confirmation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Cleared - TTS PMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
        }
        .success {
            color: #28a745;
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅</div>
        <h1>Session Cleared Successfully</h1>
        <p>All sessions and cookies have been cleared. The redirect loop issue should now be resolved.</p>
        
        <a href="login.php" class="btn">Admin Login</a>
        <a href="../packages/web/auth/sign-in.php" class="btn">User Login</a>
    </div>
</body>
</html>
