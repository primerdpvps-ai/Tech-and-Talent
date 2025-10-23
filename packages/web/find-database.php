<?php
// Database Discovery Tool
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Discovery Tool</h2>";
echo "<p>This tool will help find your correct database name and check permissions.</p>";

$host = 'localhost';
$username = 'prizmaso_admin';
$password = 'mw__2m2;{%Qp-,2S';
$port = 3306;

// Possible database names to try
$possibleDatabases = [
    'prizmaso_tts_pms',
    'prizma_tts_pms', 
    'prizmaso_ttspms',
    'prizma_ttspms',
    'tts_pms',
    'ttspms'
];

echo "<h3>Testing Database Names:</h3>";

$connectedDatabase = null;

foreach ($possibleDatabases as $dbName) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<strong>Testing: $dbName</strong><br>";
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        
        echo "✅ <span style='color: green;'><strong>SUCCESS!</strong> Connected to: $dbName</span><br>";
        
        // Get database info
        $stmt = $pdo->query("SELECT DATABASE() as current_db");
        $result = $stmt->fetch();
        echo "Confirmed database: " . $result['current_db'] . "<br>";
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "<br>";
        
        if (count($tables) > 0) {
            echo "Sample tables: " . implode(', ', array_slice($tables, 0, 5)) . "<br>";
        } else {
            echo "<span style='color: orange;'>⚠️ Database is empty - you'll need to import the schema</span><br>";
        }
        
        $connectedDatabase = $dbName;
        
    } catch (PDOException $e) {
        echo "❌ <span style='color: red;'>FAILED:</span> " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
}

if ($connectedDatabase) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Database Found!</h3>";
    echo "<p><strong>Your correct database name is:</strong> <code>$connectedDatabase</code></p>";
    echo "<p>Use this database name in your configuration files.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ No Database Found</h3>";
    echo "<p>None of the common database names worked. Please:</p>";
    echo "<ol>";
    echo "<li>Check your cPanel MySQL Databases section</li>";
    echo "<li>Look for any database that might be related to TTS PMS</li>";
    echo "<li>Create a new database if none exists</li>";
    echo "<li>Ensure the user 'prizmaso_admin' has access to the database</li>";
    echo "</ol>";
    echo "</div>";
}

// Also try to connect without specifying a database to list all available databases
echo "<h3>Available Databases:</h3>";
try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px;'>";
    echo "<h4>Databases you have access to:</h4>";
    echo "<ul>";
    foreach ($databases as $db) {
        // Skip system databases
        if (!in_array($db, ['information_schema', 'mysql', 'performance_schema', 'sys'])) {
            echo "<li><strong>$db</strong></li>";
        }
    }
    echo "</ul>";
    echo "<p><em>If you see a database name that looks like it could be for TTS PMS, try using that name.</em></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<p>Could not list databases: " . $e->getMessage() . "</p>";
    echo "</div>";
}

?>
<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f8f9fa;
    line-height: 1.6;
}
h2, h3 { 
    color: #1266f1; 
}
code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>
