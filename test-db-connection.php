<?php
/**
 * Database Connection Test Script
 * Tests the database connection with cPanel credentials
 */

// Load configuration
require_once 'config/init.php';

echo "<h2>TTS PMS Database Connection Test</h2>\n";
echo "<hr>\n";

// Test 1: Configuration Loading
echo "<h3>1. Configuration Test</h3>\n";
try {
    echo "✓ Configuration files loaded successfully<br>\n";
    echo "✓ APP_DEBUG: " . (APP_DEBUG ? 'Enabled' : 'Disabled') . "<br>\n";
    echo "✓ APP_ENV: " . APP_ENV . "<br>\n";
} catch (Exception $e) {
    echo "✗ Configuration error: " . $e->getMessage() . "<br>\n";
}

// Test 2: Database Connection
echo "<h3>2. Database Connection Test</h3>\n";
try {
    $db = Database::getInstance();
    echo "✓ Database instance created successfully<br>\n";
    
    // Test connection
    $result = $db->fetchOne('SELECT 1 as test');
    if ($result && $result['test'] == 1) {
        echo "✓ Database connection successful<br>\n";
    } else {
        echo "✗ Database connection test failed<br>\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>\n";
    echo "Error details: " . $e->getTraceAsString() . "<br>\n";
}

// Test 3: Tables Existence
echo "<h3>3. Database Tables Test</h3>\n";
try {
    $db = Database::getInstance();
    
    $tables = [
        'tts_users',
        'tts_evaluations', 
        'tts_job_positions',
        'tts_job_applications',
        'tts_page_layouts',
        'system_settings'
    ];
    
    foreach ($tables as $table) {
        try {
            $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
            if ($result) {
                echo "✓ Table '$table' exists<br>\n";
            } else {
                echo "✗ Table '$table' missing<br>\n";
            }
        } catch (Exception $e) {
            echo "✗ Error checking table '$table': " . $e->getMessage() . "<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Tables test failed: " . $e->getMessage() . "<br>\n";
}

// Test 4: Sample Data
echo "<h3>4. Sample Data Test</h3>\n";
try {
    $db = Database::getInstance();
    
    // Check users
    $userCount = $db->fetchOne('SELECT COUNT(*) as count FROM tts_users');
    echo "✓ Users in database: " . ($userCount['count'] ?? 0) . "<br>\n";
    
    // Check demo users
    $demoUser = $db->fetchOne("SELECT email, role FROM tts_users WHERE email = 'visitor@demo.com'");
    if ($demoUser) {
        echo "✓ Demo user found: " . $demoUser['email'] . " (Role: " . $demoUser['role'] . ")<br>\n";
    } else {
        echo "✗ Demo user not found<br>\n";
    }
    
} catch (Exception $e) {
    echo "✗ Sample data test failed: " . $e->getMessage() . "<br>\n";
}

// Test 5: Session Test
echo "<h3>5. Session Test</h3>\n";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Session started successfully<br>\n";
    echo "✓ Session ID: " . session_id() . "<br>\n";
} else {
    echo "✗ Session failed to start<br>\n";
}

// Test 6: File Permissions
echo "<h3>6. File Permissions Test</h3>\n";
$testFiles = [
    'config/database.php',
    'packages/web/auth/sign-in.php',
    'packages/web/dashboard/visitor/index.php'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "✓ File '$file' is readable<br>\n";
        } else {
            echo "✗ File '$file' is not readable<br>\n";
        }
    } else {
        echo "✗ File '$file' does not exist<br>\n";
    }
}

echo "<hr>\n";
echo "<h3>Test Complete</h3>\n";
echo "<p><a href='packages/web/auth/sign-in.php'>Go to Sign In</a> | <a href='index.php'>Back to Home</a></p>\n";
?>
