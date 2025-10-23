<?php
/**
 * TTS PMS - Database Initialization Script
 * Initializes database with all required tables and sample data
 */

require_once 'init.php';

function initializeDatabase() {
    try {
        $db = Database::getInstance();
        
        echo "Starting database initialization...\n";
        
        // Read and execute the schema file
        $schemaPath = dirname(__DIR__) . '/database/schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Schema file not found: $schemaPath");
        }
        
        $sql = file_get_contents($schemaPath);
        
        if ($sql === false) {
            throw new Exception("Failed to read schema file");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if (empty(trim($statement))) continue;
            
            try {
                $db->query($statement);
                $successCount++;
                
                // Show progress for major operations
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`([^`]+)`/', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "✓ Created table: $tableName\n";
                } elseif (stripos($statement, 'INSERT') !== false) {
                    preg_match('/INSERT.*?INTO\s+`?([^\s`(]+)`?/', $statement, $matches);
                    $tableName = $matches[1] ?? 'unknown';
                    echo "✓ Inserted data into: $tableName\n";
                }
                
            } catch (Exception $e) {
                $errorCount++;
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
        
        echo "\nDatabase initialization completed!\n";
        echo "Successful operations: $successCount\n";
        echo "Failed operations: $errorCount\n";
        
        // Verify critical tables exist
        $criticalTables = [
            'tts_users', 'tts_evaluations', 'tts_time_entries', 
            'tts_payslips', 'tts_leave_requests', 'tts_leave_balances',
            'tts_daily_tasks', 'tts_onboarding_tasks', 'tts_training_modules'
        ];
        
        echo "\nVerifying critical tables...\n";
        foreach ($criticalTables as $table) {
            try {
                $result = $db->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->rowCount() > 0) {
                    echo "✓ Table exists: $table\n";
                } else {
                    echo "✗ Table missing: $table\n";
                }
            } catch (Exception $e) {
                echo "✗ Error checking table $table: " . $e->getMessage() . "\n";
            }
        }
        
        // Check sample data
        echo "\nVerifying sample data...\n";
        try {
            $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users")['count'];
            echo "✓ Sample users: $userCount\n";
            
            $moduleCount = $db->fetchOne("SELECT COUNT(*) as count FROM tts_training_modules")['count'];
            echo "✓ Training modules: $moduleCount\n";
            
        } catch (Exception $e) {
            echo "✗ Error checking sample data: " . $e->getMessage() . "\n";
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "Database initialization failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run initialization if called directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "TTS PMS Database Initialization\n";
    echo "==============================\n\n";
    
    $success = initializeDatabase();
    
    if ($success) {
        echo "\n🎉 Database initialization completed successfully!\n";
        echo "You can now use the TTS PMS system with all features enabled.\n\n";
        echo "Demo Login Credentials:\n";
        echo "- Admin: admin@tts-pms.com / password\n";
        echo "- CEO: ceo@tts-pms.com / password\n";
        echo "- Manager: manager@tts-pms.com / password\n";
        echo "- Employee: employee@tts-pms.com / password\n";
        echo "- New Employee: newemployee@tts-pms.com / password\n";
        echo "- Candidate: candidate@tts-pms.com / password\n";
        echo "- Visitor: visitor@tts-pms.com / password\n";
    } else {
        echo "\n❌ Database initialization failed!\n";
        echo "Please check the error messages above and try again.\n";
        exit(1);
    }
}
?>
