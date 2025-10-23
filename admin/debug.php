<?php
/**
 * TTS PMS - Admin Debug Script
 * Helps troubleshoot session and redirect issues
 */

// Start session
session_start();

// Clear session if requested
if (isset($_GET['clear'])) {
    $_SESSION = array();
    session_destroy();
    echo "<div style='color: green; font-weight: bold;'>Session cleared!</div>";
    echo "<a href='debug.php'>Refresh</a><br><br>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>TTS PMS Admin Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        pre { background: white; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>TTS PMS Admin Debug Panel</h1>
    
    <div class="debug-box">
        <h3>Quick Actions</h3>
        <a href="login.php" style="margin-right: 10px;">Go to Login</a>
        <a href="index.php" style="margin-right: 10px;">Go to Dashboard</a>
        <a href="debug.php?clear=1" style="margin-right: 10px;">Clear Session</a>
        <a href="../" style="margin-right: 10px;">Main Site</a>
    </div>
    
    <div class="debug-box">
        <h3>Session Status</h3>
        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
        <p><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></p>
        
        <?php if (!empty($_SESSION)): ?>
            <div class="success">
                <strong>Session Variables Found:</strong>
                <pre><?php print_r($_SESSION); ?></pre>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>No Session Variables Set</strong>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="debug-box">
        <h3>Admin Login Status</h3>
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
            <div class="success">
                ✅ Admin is logged in!
                <ul>
                    <li>User ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></li>
                    <li>Email: <?php echo $_SESSION['email'] ?? 'Not set'; ?></li>
                    <li>Role: <?php echo $_SESSION['role'] ?? 'Not set'; ?></li>
                    <li>Login Time: <?php echo isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'Not set'; ?></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="error">
                ❌ Admin is NOT logged in
                <p>The session variable 'admin_logged_in' is not set or not true.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="debug-box">
        <h3>Database Connection Test</h3>
        <?php
        try {
            require_once '../config/init.php';
            $db = Database::getInstance();
            echo "<div class='success'>✅ Database connection successful!</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
    </div>
    
    <div class="debug-box">
        <h3>File Permissions</h3>
        <?php
        $files_to_check = [
            'login.php' => 'Admin Login Page',
            'index.php' => 'Admin Dashboard',
            'dashboard.php' => 'Admin Dashboard Alt',
            'logout.php' => 'Admin Logout'
        ];
        
        foreach ($files_to_check as $file => $description) {
            if (file_exists($file)) {
                $perms = substr(sprintf('%o', fileperms($file)), -4);
                echo "<p>✅ <strong>$file</strong> ($description) - Permissions: $perms</p>";
            } else {
                echo "<p>❌ <strong>$file</strong> ($description) - File not found!</p>";
            }
        }
        ?>
    </div>
    
    <div class="debug-box">
        <h3>Server Information</h3>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
        <p><strong>Current Script:</strong> <?php echo $_SERVER['PHP_SELF'] ?? 'Unknown'; ?></p>
        <p><strong>Request URI:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'Unknown'; ?></p>
    </div>
    
    <div class="debug-box">
        <h3>Test Admin Login</h3>
        <form method="POST" action="login.php" style="background: white; padding: 15px; border-radius: 5px;">
            <p><strong>Default Admin Credentials:</strong></p>
            <p>Email: <input type="email" name="email" value="admin@tts-pms.com" style="width: 200px;"></p>
            <p>Password: <input type="password" name="password" value="admin123" style="width: 200px;"></p>
            <p><button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">Test Login</button></p>
        </form>
    </div>
    
    <div class="debug-box">
        <h3>Redirect Test</h3>
        <p>Click this link to test if redirects work properly:</p>
        <a href="login.php" target="_blank">Open Login in New Tab</a>
    </div>
    
    <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 5px;">
        <h4>Troubleshooting Steps:</h4>
        <ol>
            <li>Clear your browser cookies and cache</li>
            <li>Check if session variables are being set correctly</li>
            <li>Verify database connection is working</li>
            <li>Test login with default credentials: admin@tts-pms.com / admin123</li>
            <li>Check server error logs for any PHP errors</li>
        </ol>
    </div>
</body>
</html>
