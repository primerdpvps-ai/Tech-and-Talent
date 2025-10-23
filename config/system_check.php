<?php
/**
 * TTS PMS - System Status Check
 * Comprehensive system health and feature verification
 */

require_once 'init.php';

function checkSystemStatus() {
    $status = [
        'overall' => 'healthy',
        'database' => 'unknown',
        'tables' => [],
        'features' => [],
        'configuration' => [],
        'files' => [],
        'permissions' => [],
        'errors' => [],
        'warnings' => []
    ];
    
    // Check database connection
    try {
        $db = Database::getInstance();
        $status['database'] = 'connected';
        
        // Check critical tables
        $criticalTables = [
            'tts_users' => 'User Management',
            'tts_evaluations' => 'Visitor Evaluation System',
            'tts_time_entries' => 'Time Tracking System',
            'tts_payslips' => 'Payroll System',
            'tts_leave_requests' => 'Leave Management',
            'tts_leave_balances' => 'Leave Balance Tracking',
            'tts_daily_tasks' => 'Task Management',
            'tts_onboarding_tasks' => 'Onboarding System',
            'tts_training_modules' => 'Training System'
        ];
        
        foreach ($criticalTables as $table => $description) {
            try {
                $result = $db->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->rowCount() > 0) {
                    $status['tables'][$table] = [
                        'status' => 'exists',
                        'description' => $description
                    ];
                } else {
                    $status['tables'][$table] = [
                        'status' => 'missing',
                        'description' => $description
                    ];
                    $status['errors'][] = "Critical table missing: $table ($description)";
                }
            } catch (Exception $e) {
                $status['tables'][$table] = [
                    'status' => 'error',
                    'description' => $description,
                    'error' => $e->getMessage()
                ];
                $status['errors'][] = "Error checking table $table: " . $e->getMessage();
            }
        }
        
        // Check sample data
        try {
            $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE role != 'visitor'")['count'];
            if ($userCount > 0) {
                $status['features']['sample_users'] = [
                    'status' => 'available',
                    'count' => $userCount,
                    'description' => 'Demo user accounts for testing'
                ];
            } else {
                $status['warnings'][] = 'No sample users found - run database initialization';
            }
        } catch (Exception $e) {
            $status['errors'][] = "Error checking sample data: " . $e->getMessage();
        }
        
    } catch (Exception $e) {
        $status['database'] = 'error';
        $status['errors'][] = "Database connection failed: " . $e->getMessage();
    }
    
    // Check critical files
    $criticalFiles = [
        'packages/web/dashboard/visitor/evaluation.php' => 'Visitor Evaluation Form',
        'packages/web/dashboard/employee/payslip.php' => 'Employee Payslip System',
        'packages/web/dashboard/employee/leave.php' => 'Employee Leave Management',
        'packages/web/dashboard/employee/timesheet.php' => 'Employee Time Tracking',
        'packages/web/dashboard/new-employee/timesheet.php' => 'New Employee Time Tracking',
        'packages/web/dashboard/new-employee/payslip.php' => 'New Employee Payslip',
        'packages/web/dashboard/new-employee/leave.php' => 'New Employee Leave Management',
        'packages/web/dashboard/manager/payslip.php' => 'Manager Payslip System',
        'packages/web/dashboard/manager/leave.php' => 'Manager Leave Management',
        'packages/web/dashboard/ceo/payslip.php' => 'CEO Payslip System',
        'packages/web/dashboard/ceo/leave.php' => 'CEO Leave Management'
    ];
    
    foreach ($criticalFiles as $file => $description) {
        $fullPath = TTS_PMS_ROOT . '/' . $file;
        if (file_exists($fullPath)) {
            $status['files'][$file] = [
                'status' => 'exists',
                'description' => $description,
                'size' => filesize($fullPath)
            ];
        } else {
            $status['files'][$file] = [
                'status' => 'missing',
                'description' => $description
            ];
            $status['errors'][] = "Critical file missing: $file ($description)";
        }
    }
    
    // Check directory permissions
    $criticalDirectories = [
        'uploads' => 'File Upload Directory',
        'uploads/profiles' => 'Profile Pictures Directory',
        'uploads/documents' => 'Document Upload Directory',
        'backups' => 'Backup Directory',
        'logs' => 'Log Files Directory'
    ];
    
    foreach ($criticalDirectories as $dir => $description) {
        $fullPath = TTS_PMS_ROOT . '/' . $dir;
        
        if (!is_dir($fullPath)) {
            // Try to create directory
            if (mkdir($fullPath, 0755, true)) {
                $status['permissions'][$dir] = [
                    'status' => 'created',
                    'description' => $description,
                    'writable' => is_writable($fullPath)
                ];
            } else {
                $status['permissions'][$dir] = [
                    'status' => 'missing',
                    'description' => $description
                ];
                $status['errors'][] = "Cannot create directory: $dir ($description)";
            }
        } else {
            $status['permissions'][$dir] = [
                'status' => 'exists',
                'description' => $description,
                'writable' => is_writable($fullPath)
            ];
            
            if (!is_writable($fullPath)) {
                $status['warnings'][] = "Directory not writable: $dir ($description)";
            }
        }
    }
    
    // Check PHP extensions
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'curl'];
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            $status['configuration'][$ext] = [
                'status' => 'loaded',
                'description' => "PHP Extension: $ext"
            ];
        } else {
            $status['configuration'][$ext] = [
                'status' => 'missing',
                'description' => "PHP Extension: $ext"
            ];
            $status['errors'][] = "Required PHP extension missing: $ext";
        }
    }
    
    // Check configuration constants
    $requiredConstants = ['APP_NAME', 'SALARY_STRUCTURES', 'LEAVE_ENTITLEMENTS', 'TIME_TRACKING_SETTINGS'];
    foreach ($requiredConstants as $const) {
        if (defined($const)) {
            $status['configuration'][$const] = [
                'status' => 'defined',
                'description' => "Configuration: $const"
            ];
        } else {
            $status['configuration'][$const] = [
                'status' => 'missing',
                'description' => "Configuration: $const"
            ];
            $status['warnings'][] = "Configuration constant not defined: $const";
        }
    }
    
    // Determine overall status
    if (!empty($status['errors'])) {
        $status['overall'] = 'critical';
    } elseif (!empty($status['warnings'])) {
        $status['overall'] = 'warning';
    } else {
        $status['overall'] = 'healthy';
    }
    
    return $status;
}

// Generate HTML report
function generateHTMLReport($status) {
    $statusColor = [
        'healthy' => 'success',
        'warning' => 'warning',
        'critical' => 'danger'
    ];
    
    $color = $statusColor[$status['overall']] ?? 'secondary';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TTS PMS System Status</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container my-5">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-<?php echo $color; ?> text-white">
                            <h3 class="mb-0">
                                <i class="fas fa-heartbeat me-2"></i>
                                TTS PMS System Status
                                <span class="badge bg-light text-dark ms-2"><?php echo strtoupper($status['overall']); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            
                            <!-- Overall Status -->
                            <div class="alert alert-<?php echo $color; ?> mb-4">
                                <h5>
                                    <i class="fas fa-<?php echo $status['overall'] === 'healthy' ? 'check-circle' : ($status['overall'] === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?> me-2"></i>
                                    System Status: <?php echo ucfirst($status['overall']); ?>
                                </h5>
                                <p class="mb-0">
                                    <?php if ($status['overall'] === 'healthy'): ?>
                                        All systems are operational and functioning correctly.
                                    <?php elseif ($status['overall'] === 'warning'): ?>
                                        System is functional but has some warnings that should be addressed.
                                    <?php else: ?>
                                        Critical issues detected that may affect system functionality.
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <!-- Errors -->
                            <?php if (!empty($status['errors'])): ?>
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-circle me-2"></i>Critical Issues</h6>
                                <ul class="mb-0">
                                    <?php foreach ($status['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Warnings -->
                            <?php if (!empty($status['warnings'])): ?>
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Warnings</h6>
                                <ul class="mb-0">
                                    <?php foreach ($status['warnings'] as $warning): ?>
                                        <li><?php echo htmlspecialchars($warning); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Database Status -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-database me-2"></i>Database Status</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <span class="badge bg-<?php echo $status['database'] === 'connected' ? 'success' : 'danger'; ?> mb-2">
                                                <?php echo ucfirst($status['database']); ?>
                                            </span>
                                            
                                            <?php if (!empty($status['tables'])): ?>
                                            <h6 class="mt-3">Tables Status</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <?php foreach ($status['tables'] as $table => $info): ?>
                                                    <tr>
                                                        <td><?php echo $table; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $info['status'] === 'exists' ? 'success' : 'danger'; ?>">
                                                                <?php echo $info['status']; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </table>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Files Status -->
                                <div class="col-md-6">
                                    <h5><i class="fas fa-file-code me-2"></i>Critical Files</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <?php 
                                            $existingFiles = array_filter($status['files'], fn($file) => $file['status'] === 'exists');
                                            $missingFiles = array_filter($status['files'], fn($file) => $file['status'] === 'missing');
                                            ?>
                                            <p>
                                                <span class="badge bg-success"><?php echo count($existingFiles); ?> Found</span>
                                                <span class="badge bg-danger"><?php echo count($missingFiles); ?> Missing</span>
                                            </p>
                                            
                                            <?php if (!empty($missingFiles)): ?>
                                            <h6 class="text-danger">Missing Files:</h6>
                                            <ul class="list-unstyled">
                                                <?php foreach ($missingFiles as $file => $info): ?>
                                                <li><small><i class="fas fa-times text-danger me-1"></i><?php echo $file; ?></small></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Features Status -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-cogs me-2"></i>System Features</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <?php if (!empty($status['features'])): ?>
                                                <?php foreach ($status['features'] as $feature => $info): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span><?php echo $info['description']; ?></span>
                                                    <span class="badge bg-<?php echo $info['status'] === 'available' ? 'success' : 'warning'; ?>">
                                                        <?php echo $info['status']; ?>
                                                        <?php if (isset($info['count'])): ?>
                                                            (<?php echo $info['count']; ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted">No feature data available</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Configuration Status -->
                                <div class="col-md-6">
                                    <h5><i class="fas fa-sliders-h me-2"></i>Configuration</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <?php if (!empty($status['configuration'])): ?>
                                                <?php foreach ($status['configuration'] as $config => $info): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span><small><?php echo $config; ?></small></span>
                                                    <span class="badge bg-<?php echo $info['status'] === 'loaded' || $info['status'] === 'defined' ? 'success' : 'danger'; ?>">
                                                        <?php echo $info['status']; ?>
                                                    </span>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="mt-4 text-center">
                                <a href="database_init.php" class="btn btn-primary me-2">
                                    <i class="fas fa-database me-2"></i>Initialize Database
                                </a>
                                <button onclick="location.reload()" class="btn btn-secondary">
                                    <i class="fas fa-sync me-2"></i>Refresh Status
                                </button>
                            </div>
                        </div>
                        <div class="card-footer text-muted text-center">
                            <small>Last checked: <?php echo date('Y-m-d H:i:s'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Run system check
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $status = checkSystemStatus();
    
    // Output HTML report
    echo generateHTMLReport($status);
}

?>
