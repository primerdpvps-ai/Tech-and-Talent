<?php
// Simple Database Connection Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Database Connection Test</h2>";

// Direct connection test with your credentials
$host = 'localhost';
$username = 'prizmaso_admin';
$password = 'mw__2m2;{%Qp-,2S';
$database = 'prizmaso_tts_pms';
$port = 3306;

echo "<h3>Connection Details:</h3>";
echo "Host: $host<br>";
echo "Username: $username<br>";
echo "Database: $database<br>";
echo "Port: $port<br>";
echo "Password: [HIDDEN]<br><br>";

try {
    echo "<h3>Testing Connection...</h3>";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "✅ <strong style='color: green;'>SUCCESS: Database connection established!</strong><br><br>";
    
    // Test database info
    echo "<h3>Database Information:</h3>";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "MySQL Version: " . $result['version'] . "<br>";
    
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "Current Database: " . $result['current_db'] . "<br>";
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database: " . count($tables) . "<br>";
    
    if (count($tables) > 0) {
        echo "<h4>Available Tables:</h4>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No tables found. You may need to import the database schema.</p>";
    }
    
    // Test a simple query if users table exists
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
        $result = $stmt->fetch();
        echo "Users in database: " . $result['user_count'] . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong style='color: red;'>ERROR: Database connection failed!</strong><br>";
    echo "Error Message: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br><br>";
    
    echo "<h3>Possible Solutions:</h3>";
    echo "<ul>";
    echo "<li>Check if the database name 'prizma_tts_pms' exists in your cPanel</li>";
    echo "<li>Verify the username 'prizmaso_admin' has access to this database</li>";
    echo "<li>Confirm the password is correct</li>";
    echo "<li>Make sure the database user has proper permissions</li>";
    echo "<li>Check if MySQL service is running</li>";
    echo "</ul>";
}

echo "<h3>PHP Extensions:</h3>";
echo "PDO: " . (extension_loaded('pdo') ? "✅ Available" : "❌ Missing") . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? "✅ Available" : "❌ Missing") . "<br>";

echo "<h3>Server Info:</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";

?>
<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f5f5f5;
}
h2 { 
    color: #1266f1; 
    background: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
h3 { 
    color: #333; 
    border-bottom: 2px solid #1266f1; 
    padding-bottom: 5px; 
    margin-top: 20px;
}
ul {
    background: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>
