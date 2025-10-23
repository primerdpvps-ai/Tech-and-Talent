<?php
/**
 * TTS PMS Phase 6 - Observability Helpers
 * Health monitoring, error aggregation, and system metrics
 */

/**
 * Health dashboard manager
 */
class HealthDashboard {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get comprehensive health metrics
     */
    public function getHealthMetrics() {
        return [
            'database' => $this->getDatabaseHealth(),
            'queue' => $this->getQueueHealth(),
            'backup' => $this->getBackupHealth(),
            'errors' => $this->getErrorRates(),
            'performance' => $this->getPerformanceMetrics(),
            'security' => $this->getSecurityMetrics()
        ];
    }
    
    /**
     * Get database health metrics
     */
    private function getDatabaseHealth() {
        try {
            $startTime = microtime(true);
            
            // Test query latency
            $this->db->fetchOne("SELECT 1 as test");
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            
            // Get connection count
            $connections = $this->db->fetchOne("SHOW STATUS LIKE 'Threads_connected'");
            $maxConnections = $this->db->fetchOne("SHOW VARIABLES LIKE 'max_connections'");
            
            // Get database size
            $dbSize = $this->db->fetchOne("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            
            // Check for slow queries
            $slowQueries = $this->db->fetchOne("SHOW STATUS LIKE 'Slow_queries'");
            
            $status = 'healthy';
            if ($latency > 100) $status = 'warning';
            if ($latency > 500) $status = 'critical';
            
            return [
                'status' => $status,
                'latency_ms' => $latency,
                'connections' => [
                    'current' => (int)$connections['Value'],
                    'max' => (int)$maxConnections['Value'],
                    'usage_percent' => round(($connections['Value'] / $maxConnections['Value']) * 100, 1)
                ],
                'size_mb' => (float)$dbSize['size_mb'],
                'slow_queries' => (int)$slowQueries['Value']
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get sync queue health
     */
    private function getQueueHealth() {
        try {
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    AVG(CASE WHEN status = 'completed' AND completed_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, created_at, completed_at) ELSE NULL END) as avg_processing_time
                FROM tts_admin_sync 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $depth = (int)$stats['pending'];
            $failureRate = $stats['total'] > 0 ? round(($stats['failed'] / $stats['total']) * 100, 1) : 0;
            
            $status = 'healthy';
            if ($depth > 50 || $failureRate > 5) $status = 'warning';
            if ($depth > 100 || $failureRate > 15) $status = 'critical';
            
            return [
                'status' => $status,
                'depth' => $depth,
                'stats' => [
                    'total' => (int)$stats['total'],
                    'pending' => (int)$stats['pending'],
                    'processing' => (int)$stats['processing'],
                    'completed' => (int)$stats['completed'],
                    'failed' => (int)$stats['failed']
                ],
                'failure_rate_percent' => $failureRate,
                'avg_processing_time_seconds' => round((float)$stats['avg_processing_time'], 2)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get backup system health
     */
    private function getBackupHealth() {
        try {
            $lastBackup = $this->db->fetchOne("
                SELECT created_at, file_size, status 
                FROM tts_backups 
                WHERE backup_type = 'manual' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            if (!$lastBackup) {
                return [
                    'status' => 'warning',
                    'message' => 'No backups found'
                ];
            }
            
            $hoursSinceBackup = (time() - strtotime($lastBackup['created_at'])) / 3600;
            
            $status = 'healthy';
            if ($hoursSinceBackup > 48) $status = 'warning';
            if ($hoursSinceBackup > 72) $status = 'critical';
            
            // Get backup statistics
            $stats = $this->db->fetchOne("
                SELECT 
                    COUNT(*) as total_backups,
                    SUM(file_size) as total_size,
                    AVG(file_size) as avg_size
                FROM tts_backups 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            return [
                'status' => $status,
                'last_backup' => [
                    'created_at' => $lastBackup['created_at'],
                    'hours_ago' => round($hoursSinceBackup, 1),
                    'file_size_mb' => round($lastBackup['file_size'] / 1024 / 1024, 2),
                    'status' => $lastBackup['status']
                ],
                'stats' => [
                    'total_backups' => (int)$stats['total_backups'],
                    'total_size_mb' => round($stats['total_size'] / 1024 / 1024, 2),
                    'avg_size_mb' => round($stats['avg_size'] / 1024 / 1024, 2)
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get error rates and patterns
     */
    private function getErrorRates() {
        try {
            // Get error counts by type
            $errorStats = $this->db->fetchAll("
                SELECT 
                    action_type,
                    COUNT(*) as error_count,
                    MAX(created_at) as last_error
                FROM tts_admin_edits 
                WHERE action_type LIKE '%_failed' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY action_type
                ORDER BY error_count DESC
            ");
            
            $totalErrors = array_sum(array_column($errorStats, 'error_count'));
            
            // Get total operations for error rate calculation
            $totalOps = $this->db->fetchOne("
                SELECT COUNT(*) as total 
                FROM tts_admin_edits 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $errorRate = $totalOps['total'] > 0 ? round(($totalErrors / $totalOps['total']) * 100, 2) : 0;
            
            $status = 'healthy';
            if ($errorRate > 2) $status = 'warning';
            if ($errorRate > 5) $status = 'critical';
            
            return [
                'status' => $status,
                'error_rate_percent' => $errorRate,
                'total_errors_24h' => $totalErrors,
                'error_breakdown' => $errorStats
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics() {
        try {
            // Get average response times from recent operations
            $avgTimes = $this->db->fetchOne("
                SELECT 
                    AVG(CASE WHEN action_type = 'page_update' THEN 
                        JSON_EXTRACT(changes, '$.duration') ELSE NULL END) as avg_page_update,
                    AVG(CASE WHEN action_type = 'backup_create' THEN 
                        JSON_EXTRACT(changes, '$.duration') ELSE NULL END) as avg_backup_time
                FROM tts_admin_edits 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND JSON_VALID(changes)
            ");
            
            // Check cache hit rates (if available)
            $cacheDir = dirname(__DIR__) . '/cache/';
            $cacheSize = 0;
            if (is_dir($cacheDir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($files as $file) {
                    $cacheSize += $file->getSize();
                }
            }
            
            return [
                'status' => 'healthy',
                'avg_page_update_seconds' => round((float)$avgTimes['avg_page_update'], 2),
                'avg_backup_time_seconds' => round((float)$avgTimes['avg_backup_time'], 2),
                'cache_size_mb' => round($cacheSize / 1024 / 1024, 2)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get security metrics
     */
    private function getSecurityMetrics() {
        try {
            // Get failed login attempts
            $failedLogins = $this->db->fetchOne("
                SELECT COUNT(*) as failed_attempts
                FROM tts_admin_edits 
                WHERE action_type = 'login_failed' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            // Get rate limit triggers
            $rateLimits = $this->db->fetchOne("
                SELECT COUNT(*) as rate_limit_triggers
                FROM tts_admin_edits 
                WHERE action_type = 'rate_limit_triggered' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            // Check for suspicious activity
            $suspiciousIPs = $this->db->fetchAll("
                SELECT ip_address, COUNT(*) as attempt_count
                FROM tts_admin_edits 
                WHERE action_type IN ('login_failed', 'rate_limit_triggered')
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY ip_address
                HAVING attempt_count > 10
                ORDER BY attempt_count DESC
            ");
            
            $status = 'healthy';
            if ($failedLogins['failed_attempts'] > 50 || count($suspiciousIPs) > 0) $status = 'warning';
            if ($failedLogins['failed_attempts'] > 100 || count($suspiciousIPs) > 3) $status = 'critical';
            
            return [
                'status' => $status,
                'failed_logins_24h' => (int)$failedLogins['failed_attempts'],
                'rate_limit_triggers_24h' => (int)$rateLimits['rate_limit_triggers'],
                'suspicious_ips' => array_slice($suspiciousIPs, 0, 5) // Top 5
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Error aggregation and alerting
 */
class ErrorAggregator {
    private $db;
    private $alertThresholds = [
        'error_spike' => 10, // errors per hour
        'failure_rate' => 5,  // percentage
        'critical_errors' => 3 // critical errors per hour
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check for error spikes and send alerts
     */
    public function checkErrorSpikes() {
        $currentHour = date('Y-m-d H:00:00');
        
        // Get error counts for current hour
        $errorCounts = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_errors,
                SUM(CASE WHEN action_type LIKE '%_failed' THEN 1 ELSE 0 END) as failed_operations,
                SUM(CASE WHEN action_type = 'system_error' THEN 1 ELSE 0 END) as critical_errors
            FROM tts_admin_edits 
            WHERE created_at >= ?
        ", [$currentHour]);
        
        $alerts = [];
        
        // Check for error spike
        if ($errorCounts['total_errors'] > $this->alertThresholds['error_spike']) {
            $alerts[] = [
                'type' => 'error_spike',
                'message' => "Error spike detected: {$errorCounts['total_errors']} errors in the last hour",
                'severity' => 'warning'
            ];
        }
        
        // Check for critical errors
        if ($errorCounts['critical_errors'] > $this->alertThresholds['critical_errors']) {
            $alerts[] = [
                'type' => 'critical_errors',
                'message' => "Critical errors detected: {$errorCounts['critical_errors']} critical errors in the last hour",
                'severity' => 'critical'
            ];
        }
        
        // Send alerts if any
        foreach ($alerts as $alert) {
            $this->sendAlert($alert);
        }
        
        return $alerts;
    }
    
    /**
     * Get error patterns and trends
     */
    public function getErrorPatterns() {
        try {
            // Get error trends over last 24 hours
            $hourlyErrors = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as error_count,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM tts_admin_edits 
                WHERE action_type LIKE '%_failed' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')
                ORDER BY hour DESC
            ");
            
            // Get most common error types
            $errorTypes = $this->db->fetchAll("
                SELECT 
                    action_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    MAX(created_at) as last_occurrence
                FROM tts_admin_edits 
                WHERE action_type LIKE '%_failed' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY action_type
                ORDER BY count DESC
                LIMIT 10
            ");
            
            // Get error distribution by IP
            $ipDistribution = $this->db->fetchAll("
                SELECT 
                    ip_address,
                    COUNT(*) as error_count,
                    COUNT(DISTINCT action_type) as error_types,
                    MIN(created_at) as first_error,
                    MAX(created_at) as last_error
                FROM tts_admin_edits 
                WHERE action_type LIKE '%_failed' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY ip_address
                HAVING error_count > 5
                ORDER BY error_count DESC
                LIMIT 10
            ");
            
            return [
                'hourly_trends' => $hourlyErrors,
                'error_types' => $errorTypes,
                'ip_distribution' => $ipDistribution
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Send alert notification
     */
    private function sendAlert($alert) {
        try {
            // Get admin email from settings
            $adminEmail = $this->db->fetchOne(
                "SELECT setting_value FROM tts_settings WHERE setting_key = 'admin_email' AND category = 'system'"
            );
            
            if (!$adminEmail) {
                return false;
            }
            
            $subject = 'TTS PMS Alert: ' . ucwords(str_replace('_', ' ', $alert['type']));
            $message = $this->generateAlertEmail($alert);
            
            // Use existing email function if available
            if (function_exists('sendEmail')) {
                sendEmail($adminEmail['setting_value'], $subject, $message);
            }
            
            // Log alert
            $this->db->insert('tts_admin_edits', [
                'admin_id' => 1,
                'action_type' => 'alert_sent',
                'object_type' => 'system',
                'object_id' => $alert['type'],
                'changes' => json_encode($alert),
                'ip_address' => 'system',
                'user_agent' => 'Error Aggregator'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send alert: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate alert email content
     */
    private function generateAlertEmail($alert) {
        $timestamp = date('Y-m-d H:i:s');
        
        return "
        <h3>TTS PMS System Alert</h3>
        <p><strong>Alert Type:</strong> " . ucwords(str_replace('_', ' ', $alert['type'])) . "</p>
        <p><strong>Severity:</strong> " . strtoupper($alert['severity']) . "</p>
        <p><strong>Message:</strong> {$alert['message']}</p>
        <p><strong>Time:</strong> $timestamp</p>
        
        <h4>Recommended Actions:</h4>
        <ul>
            <li>Check the admin panel error logs</li>
            <li>Review recent system changes</li>
            <li>Monitor system performance</li>
            <li>Contact technical support if issues persist</li>
        </ul>
        
        <p><small>This is an automated alert from TTS PMS monitoring system.</small></p>
        ";
    }
}

/**
 * System metrics collector
 */
class MetricsCollector {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Collect and store system metrics
     */
    public function collectMetrics() {
        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->collectDatabaseMetrics(),
            'application' => $this->collectApplicationMetrics(),
            'system' => $this->collectSystemMetrics()
        ];
        
        // Store metrics in database
        $this->storeMetrics($metrics);
        
        return $metrics;
    }
    
    /**
     * Collect database metrics
     */
    private function collectDatabaseMetrics() {
        try {
            $startTime = microtime(true);
            $this->db->fetchOne("SELECT 1");
            $queryTime = microtime(true) - $startTime;
            
            $status = $this->db->fetchAll("SHOW STATUS WHERE Variable_name IN (
                'Connections', 'Queries', 'Slow_queries', 'Threads_connected', 
                'Bytes_received', 'Bytes_sent', 'Uptime'
            )");
            
            $metrics = ['query_time_ms' => round($queryTime * 1000, 2)];
            foreach ($status as $stat) {
                $metrics[strtolower($stat['Variable_name'])] = $stat['Value'];
            }
            
            return $metrics;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Collect application metrics
     */
    private function collectApplicationMetrics() {
        try {
            // Get active sessions
            $activeSessions = $this->db->fetchOne("
                SELECT COUNT(*) as active_sessions 
                FROM tts_admin_sessions 
                WHERE expires_at > NOW()
            ");
            
            // Get recent activity
            $recentActivity = $this->db->fetchOne("
                SELECT COUNT(*) as recent_actions 
                FROM tts_admin_edits 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            
            // Get cache statistics
            $cacheDir = dirname(__DIR__) . '/cache/';
            $cacheFiles = 0;
            $cacheSize = 0;
            
            if (is_dir($cacheDir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($files as $file) {
                    $cacheFiles++;
                    $cacheSize += $file->getSize();
                }
            }
            
            return [
                'active_sessions' => (int)$activeSessions['active_sessions'],
                'recent_actions' => (int)$recentActivity['recent_actions'],
                'cache_files' => $cacheFiles,
                'cache_size_bytes' => $cacheSize,
                'memory_usage_bytes' => memory_get_usage(true),
                'memory_peak_bytes' => memory_get_peak_usage(true)
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Collect system metrics
     */
    private function collectSystemMetrics() {
        $metrics = [
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ];
        
        // Add disk space if available
        if (function_exists('disk_free_space')) {
            $freeBytes = disk_free_space(__DIR__);
            $totalBytes = disk_total_space(__DIR__);
            
            if ($freeBytes !== false && $totalBytes !== false) {
                $metrics['disk_free_bytes'] = $freeBytes;
                $metrics['disk_total_bytes'] = $totalBytes;
                $metrics['disk_usage_percent'] = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 2);
            }
        }
        
        return $metrics;
    }
    
    /**
     * Store metrics in database
     */
    private function storeMetrics($metrics) {
        try {
            $this->db->insert('tts_system_health', [
                'check_type' => 'metrics',
                'check_name' => 'System Metrics',
                'status' => 'healthy',
                'message' => 'Metrics collected successfully',
                'details' => json_encode($metrics)
            ]);
        } catch (Exception $e) {
            error_log("Failed to store metrics: " . $e->getMessage());
        }
    }
}

/**
 * Performance monitoring functions
 */
function startPerformanceTimer($operation) {
    if (!isset($_SESSION['performance_timers'])) {
        $_SESSION['performance_timers'] = [];
    }
    
    $_SESSION['performance_timers'][$operation] = microtime(true);
}

function endPerformanceTimer($operation) {
    if (!isset($_SESSION['performance_timers'][$operation])) {
        return null;
    }
    
    $duration = microtime(true) - $_SESSION['performance_timers'][$operation];
    unset($_SESSION['performance_timers'][$operation]);
    
    // Log slow operations
    if ($duration > 2.0) { // 2 seconds threshold
        try {
            $db = Database::getInstance();
            $db->insert('tts_admin_edits', [
                'admin_id' => $_SESSION['user_id'] ?? 1,
                'action_type' => 'slow_operation',
                'object_type' => 'performance',
                'object_id' => $operation,
                'changes' => json_encode(['duration_seconds' => round($duration, 3)]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Failed to log slow operation: " . $e->getMessage());
        }
    }
    
    return round($duration, 3);
}
?>
