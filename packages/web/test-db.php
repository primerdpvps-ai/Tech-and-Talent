<?php
// Database Connection Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>TTS PMS Database Connection Test</h2>";

// Test 1: Check if config files exist
echo "<h3>1. Configuration Files Check:</h3>";
$configPath = '../../config/config.php';
$dbPath = '../../config/database.php';
$envPath = '../../.env';

echo "Config file exists: " . (file_exists($configPath) ? "✅ YES" : "❌ NO") . "<br>";
echo "Database file exists: " . (file_exists($dbPath) ? "✅ YES" : "❌ NO") . "<br>";
echo ".env file exists: " . (file_exists($envPath) ? "✅ YES" : "❌ NO") . "<br>";

// Test 2: Load configuration
echo "<h3>2. Loading Configuration:</h3>";
try {
    if (!defined('TTS_PMS_ROOT')) {
        define('TTS_PMS_ROOT', dirname(dirname(__DIR__)));
    }
    
    require_once $configPath;
    echo "✅ Config loaded successfully<br>";
    echo "Environment: " . (defined('APP_ENV') ? APP_ENV : 'Not defined') . "<br>";
    echo "Debug mode: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'ON' : 'OFF') : 'Not defined') . "<br>";
} catch (Exception $e) {
    echo "❌ Config load error: " . $e->getMessage() . "<br>";
}

// Test 3: Check environment variables
echo "<h3>3. Environment Variables:</h3>";
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    echo "✅ .env file content loaded<br>";
    
    // Parse .env manually
    $lines = explode("\n", $envContent);
    $envVars = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    echo "DB_HOST: " . ($envVars['DB_HOST'] ?? 'Not set') . "<br>";
    echo "DB_USERNAME: " . ($envVars['DB_USERNAME'] ?? 'Not set') . "<br>";
    echo "DB_DATABASE: " . ($envVars['DB_DATABASE'] ?? 'Not set') . "<br>";
    echo "DB_PASSWORD: " . (isset($envVars['DB_PASSWORD']) ? '[SET]' : 'Not set') . "<br>";
} else {
    echo "❌ .env file not found<br>";
}

// Test 4: Direct PDO connection test
echo "<h3>4. Direct Database Connection Test:</h3>";
try {
    $host = 'localhost';
    $username = 'prizmaso_admin';
    $password = 'mw__2m2;{%Qp-,2S';
    $database = 'prizma_tts_pms';
    $port = 3306;
    
    echo "Attempting connection with:<br>";
    echo "Host: $host<br>";
    echo "Username: $username<br>";
    echo "Database: $database<br>";
    echo "Port: $port<br>";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "✅ <strong>Database connection successful!</strong><br>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$database'");
    $result = $stmt->fetch();
    echo "Tables in database: " . $result['table_count'] . "<br>";
    
    // Test specific table
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $userTable = $stmt->fetch();
    echo "Users table exists: " . ($userTable ? "✅ YES" : "❌ NO") . "<br>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Database connection failed!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

// Test 5: Test with Database class
echo "<h3>5. Database Class Test:</h3>";
try {
    require_once $dbPath;
    echo "✅ Database class loaded<br>";
    
    $db = Database::getInstance();
    echo "✅ Database instance created<br>";
    
    $connection = $db->getConnection();
    echo "✅ Database connection obtained<br>";
    
    $health = db_health_check();
    echo "Connection status: " . ($health['connection'] ? "✅ Connected" : "❌ Failed") . "<br>";
    echo "Database size: " . $health['database_size'] . " MB<br>";
    
} catch (Exception $e) {
    echo "❌ Database class error: " . $e->getMessage() . "<br>";
}

// Test 6: Check PHP extensions
echo "<h3>6. PHP Extensions Check:</h3>";
echo "PDO extension: " . (extension_loaded('pdo') ? "✅ Loaded" : "❌ Missing") . "<br>";
echo "PDO MySQL extension: " . (extension_loaded('pdo_mysql') ? "✅ Loaded" : "❌ Missing") . "<br>";
echo "MySQL extension: " . (extension_loaded('mysql') ? "✅ Loaded" : "❌ Missing") . "<br>";
echo "MySQLi extension: " . (extension_loaded('mysqli') ? "✅ Loaded" : "❌ Missing") . "<br>";

echo "<h3>7. PHP Info:</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #1266f1; }
h3 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
</style>
