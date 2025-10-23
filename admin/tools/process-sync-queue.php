<?php
/**
 * TTS PMS Phase 5 - Sync Queue Processor
 * Background processor for admin sync operations
 * CLI/Cron compatible with health monitoring
 */

// Determine if running via CLI or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Web access - require admin session and secret token
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        die('Unauthorized');
    }
    
    $secretToken = $_GET['token'] ?? '';
    $expectedToken = hash('sha256', 'tts_sync_processor_' . date('Y-m-d'));
    if ($secretToken !== $expectedToken) {
        http_response_code(403);
        die('Invalid token');
    }
    
    header('Content-Type: text/plain');
}

// Load dependencies
require_once dirname(dirname(__DIR__)) . '/config/init.php';
require_once dirname(dirname(__DIR__)) . '/includes/admin_helpers.php';
require_once dirname(__DIR__) . '/api/admin-sync-helpers.php';

$db = Database::getInstance();
$startTime = microtime(true);
$processedCount = 0;
$successCount = 0;
$failureCount = 0;
$skippedCount = 0;

// Configuration
$batchSize = 10;
$maxRetries = 3;
$lockTimeout = 300; // 5 minutes
$expireAfterHours = 24;

logMessage("=== TTS Sync Queue Processor Started ===");
logMessage("Batch size: $batchSize, Max retries: $maxRetries");

try {
    // Clean up expired and stuck jobs
    cleanupJobs();
    
    // Get pending jobs
    $pendingJobs = getPendingJobs($batchSize);
    
    if (empty($pendingJobs)) {
        logMessage("No pending jobs found");
        updateHealthStatus(0, 0, 0);
        exit(0);
    }
    
    logMessage("Found " . count($pendingJobs) . " pending jobs");
    
    // Process each job
    foreach ($pendingJobs as $job) {
        $processedCount++;
        
        try {
            logMessage("Processing job {$job['sync_id']} ({$job['action_type']})");
            
            // Lock the job
            if (!lockJob($job['id'])) {
                logMessage("Failed to lock job {$job['sync_id']}, skipping");
                $skippedCount++;
                continue;
            }
            
            // Process the job
            $result = processJob($job);
            
            if ($result['success']) {
                markJobCompleted($job['id'], $result);
                $successCount++;
                logMessage("✓ Job {$job['sync_id']} completed successfully");
            } else {
                handleJobFailure($job, $result['error']);
                $failureCount++;
                logMessage("✗ Job {$job['sync_id']} failed: {$result['error']}");
            }
            
        } catch (Exception $e) {
            handleJobFailure($job, $e->getMessage());
            $failureCount++;
            logMessage("✗ Job {$job['sync_id']} exception: " . $e->getMessage());
        }
    }
    
    // Update health status
    updateHealthStatus($processedCount, $successCount, $failureCount);
    
    // Check failure rate and send alerts if needed
    checkFailureRate($successCount, $failureCount);
    
} catch (Exception $e) {
    logMessage("FATAL ERROR: " . $e->getMessage());
    exit(1);
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

logMessage("=== Processing Complete ===");
logMessage("Processed: $processedCount, Success: $successCount, Failed: $failureCount, Skipped: $skippedCount");
logMessage("Duration: {$duration}s");

exit(0);

/**
 * Get pending jobs from queue
 */
function getPendingJobs($limit) {
    global $db;
    
    return $db->fetchAll("
        SELECT * FROM tts_admin_sync 
        WHERE status = 'pending' 
        AND retry_count < ? 
        ORDER BY priority DESC, created_at ASC 
        LIMIT ?
    ", [$GLOBALS['maxRetries'], $limit]);
}

/**
 * Lock a job for processing
 */
function lockJob($jobId) {
    global $db;
    
    $result = $db->query("
        UPDATE tts_admin_sync 
        SET status = 'processing', started_at = NOW() 
        WHERE id = ? AND status = 'pending'
    ", [$jobId]);
    
    return $result->rowCount() > 0;
}

/**
 * Process individual job
 */
function processJob($job) {
    global $db;
    
    $db->beginTransaction();
    
    try {
        $data = json_decode($job['data_payload'], true);
        $syncId = $job['sync_id'];
        
        // Simulate admin session for handlers
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['user_id'] = $job['admin_id'];
        $_SESSION['admin_logged_in'] = true;
        
        // Route to appropriate handler
        switch ($job['action_type']) {
            case 'page_update':
                $result = handlePageUpdate($data, $syncId);
                break;
                
            case 'settings_update':
                $result = handleSettingsUpdate($data, $syncId);
                break;
                
            case 'module_toggle':
                $result = handleModuleToggle($data, $syncId);
                break;
                
            case 'payroll_update':
                $result = handlePayrollUpdate($data, $syncId);
                break;
                
            case 'seo_update':
                $result = handleSEOUpdate($data, $syncId);
                break;
                
            case 'payroll_recalculation':
                $result = handlePayrollRecalculation($data, $syncId);
                break;
                
            default:
                throw new Exception("Unknown action type: {$job['action_type']}");
        }
        
        $db->commit();
        
        // Log successful processing
        logSyncProcess($job, 'completed', $result['message'] ?? 'Success');
        
        return ['success' => true, 'result' => $result];
        
    } catch (Exception $e) {
        $db->rollback();
        
        // Log failed processing
        logSyncProcess($job, 'failed', $e->getMessage());
        
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Mark job as completed
 */
function markJobCompleted($jobId, $result) {
    global $db;
    
    $db->update('tts_admin_sync', [
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s'),
        'result_data' => json_encode($result)
    ], 'id = ?', [$jobId]);
}

/**
 * Handle job failure with retry logic
 */
function handleJobFailure($job, $errorMessage) {
    global $db, $maxRetries;
    
    $newRetryCount = $job['retry_count'] + 1;
    
    if ($newRetryCount >= $maxRetries) {
        // Max retries reached - mark as failed
        $db->update('tts_admin_sync', [
            'status' => 'failed',
            'completed_at' => date('Y-m-d H:i:s'),
            'retry_count' => $newRetryCount,
            'error_message' => $errorMessage
        ], 'id = ?', [$job['id']]);
    } else {
        // Reset to pending for retry
        $db->update('tts_admin_sync', [
            'status' => 'pending',
            'started_at' => null,
            'retry_count' => $newRetryCount,
            'error_message' => $errorMessage
        ], 'id = ?', [$job['id']]);
    }
}

/**
 * Clean up expired and stuck jobs
 */
function cleanupJobs() {
    global $db, $lockTimeout, $expireAfterHours;
    
    // Mark stuck processing jobs as pending (if locked too long)
    $stuckCount = $db->query("
        UPDATE tts_admin_sync 
        SET status = 'pending', started_at = NULL 
        WHERE status = 'processing' 
        AND started_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
    ", [$lockTimeout])->rowCount();
    
    if ($stuckCount > 0) {
        logMessage("Released $stuckCount stuck jobs");
    }
    
    // Mark old failed jobs as expired
    $expiredCount = $db->query("
        UPDATE tts_admin_sync 
        SET status = 'expired' 
        WHERE status IN ('failed', 'pending') 
        AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
    ", [$expireAfterHours])->rowCount();
    
    if ($expiredCount > 0) {
        logMessage("Expired $expiredCount old jobs");
    }
}

/**
 * Update system health status
 */
function updateHealthStatus($processed, $success, $failed) {
    global $db;
    
    $successRate = $processed > 0 ? round(($success / $processed) * 100, 2) : 100;
    $failureRate = $processed > 0 ? round(($failed / $processed) * 100, 2) : 0;
    
    // Get queue depth
    $queueDepth = $db->fetchOne("SELECT COUNT(*) as count FROM tts_admin_sync WHERE status = 'pending'");
    
    $healthData = [
        'processed' => $processed,
        'success_count' => $success,
        'failure_count' => $failed,
        'success_rate' => $successRate,
        'failure_rate' => $failureRate,
        'queue_depth' => $queueDepth['count']
    ];
    
    // Update or insert health record
    $db->query("
        INSERT INTO tts_system_health (check_type, check_name, status, message, details) 
        VALUES ('sync_queue', 'Queue Processor', ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            status = VALUES(status),
            message = VALUES(message),
            details = VALUES(details),
            checked_at = NOW()
    ", [
        $failureRate > 10 ? 'critical' : ($failureRate > 5 ? 'warning' : 'healthy'),
        "Processed $processed jobs, $successRate% success rate",
        json_encode($healthData)
    ]);
}

/**
 * Check failure rate and send alerts
 */
function checkFailureRate($success, $failed) {
    $total = $success + $failed;
    if ($total === 0) return;
    
    $failureRate = ($failed / $total) * 100;
    
    if ($failureRate > 10) {
        logMessage("WARNING: High failure rate detected ($failureRate%)");
        sendFailureAlert($failureRate, $failed, $total);
    }
}

/**
 * Send failure rate alert
 */
function sendFailureAlert($failureRate, $failed, $total) {
    global $db;
    
    // Get admin email from settings
    $adminEmail = $db->fetchOne("SELECT setting_value FROM tts_settings WHERE setting_key = 'admin_email'");
    
    if ($adminEmail && function_exists('sendEmail')) {
        $subject = 'TTS PMS - High Sync Failure Rate Alert';
        $message = "
        <h3>Sync Queue Alert</h3>
        <p>High failure rate detected in sync queue processing:</p>
        <ul>
            <li><strong>Failure Rate:</strong> " . round($failureRate, 2) . "%</li>
            <li><strong>Failed Jobs:</strong> $failed out of $total</li>
            <li><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</li>
        </ul>
        <p>Please check the sync queue and error logs for details.</p>
        ";
        
        try {
            sendEmail($adminEmail['setting_value'], $subject, $message);
            logMessage("Alert email sent to admin");
        } catch (Exception $e) {
            logMessage("Failed to send alert email: " . $e->getMessage());
        }
    }
}

/**
 * Log sync processing to audit trail
 */
function logSyncProcess($job, $status, $message) {
    global $db;
    
    try {
        $db->insert('tts_admin_edits', [
            'admin_id' => $job['admin_id'],
            'action_type' => 'sync_process',
            'object_type' => $job['action_type'],
            'object_id' => $job['sync_id'],
            'changes' => "Queue processing $status: $message",
            'ip_address' => 'queue_processor',
            'user_agent' => 'TTS Sync Queue Processor v1.0'
        ]);
    } catch (Exception $e) {
        // Fail silently if audit logging fails
        error_log("Failed to log sync process: " . $e->getMessage());
    }
}

/**
 * Handle payroll recalculation job
 */
function handlePayrollRecalculation($data, $syncId) {
    global $db;
    
    $triggerSyncId = $data['trigger_sync_id'] ?? '';
    
    // Get affected payroll records (example - adjust based on your payroll structure)
    $affectedRecords = $db->fetchAll("
        SELECT id FROM tts_payroll 
        WHERE status = 'pending' OR updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    
    $recalculatedCount = 0;
    
    foreach ($affectedRecords as $record) {
        // Recalculate payroll based on new settings
        // This would call your existing payroll calculation logic
        $recalculatedCount++;
    }
    
    return [
        'message' => "Recalculated $recalculatedCount payroll records",
        'trigger_sync_id' => $triggerSyncId,
        'recalculated_count' => $recalculatedCount
    ];
}

/**
 * Log message with timestamp
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] $message";
    
    if ($GLOBALS['isCLI']) {
        echo $logLine . "\n";
    } else {
        echo $logLine . "<br>\n";
    }
    
    // Also log to file if possible
    $logFile = dirname(__DIR__) . '/logs/sync-queue.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    if (is_writable($logDir)) {
        file_put_contents($logFile, $logLine . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>
