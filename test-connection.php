<?php
/**
 * Database Connection Test for cPanel
 * Use this file to test if your database connection is working
 */

// Database credentials
$host = 'localhost';
$username = 'prizmaso_admin';
$password = 'mw__2m2;{%Qp-,2S';
$database = 'prizmaso_tts_pms';

echo "<h2>TTS PMS - Database Connection Test</h2>";

try {
    // Test PDO connection
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = [
        'tts_users_meta',
        'tts_employment', 
        'tts_payroll_weeks',
        'tts_client_requests',
        'system_settings'
    ];
    
    echo "<h3>Table Check:</h3>";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->fetch()) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
    // Test system settings
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_settings");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ System settings table has {$result['count']} records</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ System settings table issue: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Server Information:</h3>";
    echo "<p><strong>MySQL Version:</strong> " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
    echo "<p><strong>Connection Status:</strong> " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Check:</strong></p>";
    echo "<ul>";
    echo "<li>Database name: $database</li>";
    echo "<li>Username: $username</li>";
    echo "<li>Host: $host</li>";
    echo "<li>Make sure the database exists in cPanel</li>";
    echo "<li>Verify the username has access to the database</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If connection is successful, delete this test file for security</li>";
echo "<li>Upload the updated config.php file to replace the old one</li>";
echo "<li>Your website should now work at <a href='https://pms.prizmasoft.com/'>https://pms.prizmasoft.com/</a></li>";
echo "<li>Admin panel: <a href='https://pms.prizmasoft.com/admin/'>https://pms.prizmasoft.com/admin/</a></li>";
echo "</ol>";

?>
