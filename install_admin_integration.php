<?php
/**
 * TTS PMS Super Admin Integration Installer
 * Idempotent installer for Phase 3-5 components
 * Compatible with PHP 8.4.11 / MariaDB 10.6.23 / cPanel
 */

// Prevent direct access in production
if (!defined('INSTALLER_ALLOWED')) {
    if (file_exists(__DIR__ . '/config/app_config.php')) {
        require_once __DIR__ . '/config/app_config.php';
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            die('Installer disabled in production. Remove this file after installation.');
        }
    }
}

$startTime = microtime(true);
$results = [];
$errors = [];
$warnings = [];

// Load configuration
require_once __DIR__ . '/config/init.php';

try {
    $db = Database::getInstance();
    
    // Run installation steps
    $results['php_check'] = checkPHPRequirements();
    $results['permissions'] = checkFilePermissions();
    $results['database'] = checkDatabaseConnection();
    $results['migrations'] = runMigrations();
    $results['seeding'] = seedDefaultData();
    $results['health_check'] = performHealthCheck();
    
    // Log installation
    logInstallation($results);
    
} catch (Exception $e) {
    $errors[] = "Fatal error: " . $e->getMessage();
    $results['status'] = 'failed';
}

// Render results
renderInstallationReport();

/**
 * Check PHP requirements and extensions
 */
function checkPHPRequirements() {
    global $errors, $warnings;
    
    $checks = [
        'php_version' => version_compare(PHP_VERSION, '8.4.0', '>='),
        'mysqli' => extension_loaded('mysqli'),
        'curl' => extension_loaded('curl'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json'),
        'zip' => extension_loaded('zip'),
        'openssl' => extension_loaded('openssl')
    ];
    
    $passed = 0;
    $total = count($checks);
    
    foreach ($checks as $check => $result) {
        if ($result) {
            $passed++;
        } else {
            $errors[] = "Missing requirement: $check";
        }
    }
    
    if (ini_get('file_uploads') != '1') {
        $warnings[] = "File uploads disabled - may affect media management";
    }
    
    return [
        'passed' => $passed,
        'total' => $total,
        'success' => $passed === $total,
        'details' => $checks
    ];
}

/**
 * Check and create required directories with proper permissions
 */
function checkFilePermissions() {
    global $warnings;
    
    $directories = [
        'admin/backups' => 0755,
        'admin/logs' => 0755,
        'packages/web/src/pages' => 0755,
        'cache' => 0755,
        'cache/settings' => 0755,
        'cache/modules' => 0755
    ];
    
    $created = 0;
    $verified = 0;
    
    foreach ($directories as $dir => $permission) {
        $fullPath = __DIR__ . '/' . $dir;
        
        if (!is_dir($fullPath)) {
            if (mkdir($fullPath, $permission, true)) {
                $created++;
            } else {
                $warnings[] = "Failed to create directory: $dir";
            }
        } else {
            $verified++;
        }
        
        // Set proper permissions
        if (is_dir($fullPath)) {
            chmod($fullPath, $permission);
        }
    }
    
    // Create .htaccess for sensitive directories
    $htaccessContent = "Order Deny,Allow\nDeny from all\n";
    $protectedDirs = ['admin/backups', 'admin/logs', 'cache'];
    
    foreach ($protectedDirs as $dir) {
        $htaccessPath = __DIR__ . '/' . $dir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
    
    return [
        'created' => $created,
        'verified' => $verified,
        'total' => count($directories),
        'success' => true
    ];
}

/**
 * Check database connection and basic functionality
 */
function checkDatabaseConnection() {
    global $db, $errors;
    
    try {
        // Test basic connection
        $result = $db->fetchOne("SELECT VERSION() as version, DATABASE() as db_name");
        
        // Check MariaDB version
        $version = $result['version'];
        $isMariaDB = strpos($version, 'MariaDB') !== false;
        $versionNumber = preg_replace('/[^0-9.].*/', '', $version);
        
        if (!$isMariaDB || version_compare($versionNumber, '10.6.0', '<')) {
            $errors[] = "MariaDB 10.6+ required, found: $version";
        }
        
        // Check charset
        $charset = $db->fetchOne("SELECT @@character_set_database as charset");
        if ($charset['charset'] !== 'utf8mb4') {
            $errors[] = "Database charset should be utf8mb4, found: " . $charset['charset'];
        }
        
        return [
            'version' => $version,
            'database' => $result['db_name'],
            'charset' => $charset['charset'],
            'success' => empty($errors)
        ];
        
    } catch (Exception $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Run all required migrations idempotently
 */
function runMigrations() {
    global $db;
    
    $migrations = [
        'phase3_cms_migrations.sql',
        'phase4_settings_migrations.sql',
        'phase5_sync_migrations.sql',
        'phase5_queue_indexes.sql',
        'phase5_backup_indexes.sql'
    ];
    
    $executed = 0;
    $skipped = 0;
    $failed = 0;
    
    foreach ($migrations as $migration) {
        $migrationPath = __DIR__ . '/database/' . $migration;
        
        if (!file_exists($migrationPath)) {
            $skipped++;
            continue;
        }
        
        try {
            $sql = file_get_contents($migrationPath);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (empty($statement) || strpos($statement, '--') === 0) continue;
                
                $db->query($statement);
            }
            
            $executed++;
            
        } catch (Exception $e) {
            $failed++;
            // Log but don't fail - migrations should be idempotent
        }
    }
    
    return [
        'executed' => $executed,
        'skipped' => $skipped,
        'failed' => $failed,
        'total' => count($migrations),
        'success' => $failed === 0
    ];
}

/**
 * Seed default data if not present
 */
function seedDefaultData() {
    global $db;
    
    $seeded = [];
    
    try {
        // Seed capabilities
        $capabilities = [
            'manage_pages' => 'Create and edit pages using Visual Builder',
            'manage_settings' => 'Configure global system settings',
            'manage_modules' => 'Enable/disable system modules',
            'manage_payroll' => 'Configure payroll rates and calculations',
            'manage_backups' => 'Create and restore system backups',
            'view_audit_log' => 'View system audit logs and changes',
            'manage_database' => 'Access database management tools',
            'manage_users' => 'Create and manage user accounts',
            'manage_system' => 'Full system administration access'
        ];
        
        foreach ($capabilities as $name => $description) {
            $existing = $db->fetchOne("SELECT id FROM tts_capabilities WHERE name = ?", [$name]);
            if (!$existing) {
                $db->insert('tts_capabilities', ['name' => $name, 'description' => $description]);
                $seeded[] = "capability:$name";
            }
        }
        
        // Ensure Super Admin role exists
        $superAdminRole = $db->fetchOne("SELECT id FROM tts_roles WHERE name = 'super_admin'");
        if (!$superAdminRole) {
            $roleId = $db->insert('tts_roles', [
                'name' => 'super_admin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all capabilities'
            ]);
            $seeded[] = "role:super_admin";
        } else {
            $roleId = $superAdminRole['id'];
        }
        
        // Assign all capabilities to Super Admin role
        $allCapabilities = $db->fetchAll("SELECT id FROM tts_capabilities");
        foreach ($allCapabilities as $capability) {
            $existing = $db->fetchOne(
                "SELECT id FROM tts_role_capabilities WHERE role_id = ? AND capability_id = ?",
                [$roleId, $capability['id']]
            );
            if (!$existing) {
                $db->insert('tts_role_capabilities', [
                    'role_id' => $roleId,
                    'capability_id' => $capability['id']
                ]);
            }
        }
        
        // Find highest privileged admin user and assign Super Admin role
        $adminUser = $db->fetchOne("
            SELECT id FROM tts_users 
            WHERE role IN ('admin', 'super_admin') 
            ORDER BY created_at ASC 
            LIMIT 1
        ");
        
        if ($adminUser) {
            $db->update('tts_users', ['role' => 'super_admin'], 'id = ?', [$adminUser['id']]);
            $seeded[] = "user_role:super_admin";
        }
        
        // Seed installation tracking
        $components = [
            'visual_builder' => '1.0.0',
            'global_settings' => '1.0.0',
            'sync_api' => '1.0.0',
            'backup_system' => '1.0.0',
            'queue_processor' => '1.0.0'
        ];
        
        foreach ($components as $component => $version) {
            $existing = $db->fetchOne("SELECT id FROM tts_installation WHERE component = ?", [$component]);
            if (!$existing) {
                $db->insert('tts_installation', [
                    'component' => $component,
                    'version' => $version,
                    'status' => 'installed',
                    'installed_by' => $adminUser['id'] ?? 1
                ]);
                $seeded[] = "component:$component";
            }
        }
        
        return [
            'seeded' => $seeded,
            'count' => count($seeded),
            'success' => true
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'seeded' => $seeded
        ];
    }
}

/**
 * Perform final health check
 */
function performHealthCheck() {
    global $db;
    
    $checks = [];
    
    try {
        // Check critical tables exist
        $requiredTables = [
            'tts_users', 'tts_settings', 'tts_admin_edits', 'tts_cms_pages',
            'tts_page_layouts', 'tts_admin_sync', 'tts_backups', 'tts_system_health'
        ];
        
        foreach ($requiredTables as $table) {
            $exists = $db->fetchOne("SHOW TABLES LIKE '$table'");
            $checks["table:$table"] = !empty($exists);
        }
        
        // Check admin user exists
        $adminExists = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE role = 'super_admin'");
        $checks['admin_user'] = $adminExists['count'] > 0;
        
        // Check settings populated
        $settingsCount = $db->fetchOne("SELECT COUNT(*) as count FROM tts_settings");
        $checks['settings'] = $settingsCount['count'] > 10;
        
        // Check modules configured
        $modulesCount = $db->fetchOne("SELECT COUNT(*) as count FROM tts_module_config");
        $checks['modules'] = $modulesCount['count'] > 0;
        
        $passed = array_sum($checks);
        $total = count($checks);
        
        return [
            'checks' => $checks,
            'passed' => $passed,
            'total' => $total,
            'success' => $passed === $total
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Log installation to audit trail
 */
function logInstallation($results) {
    global $db, $startTime;
    
    try {
        $duration = round(microtime(true) - $startTime, 2);
        $success = !in_array(false, array_column($results, 'success'));
        
        $db->insert('tts_admin_edits', [
            'admin_id' => 1,
            'action_type' => 'installer_run',
            'object_type' => 'system',
            'object_id' => 'installation',
            'changes' => json_encode([
                'results' => $results,
                'duration' => $duration,
                'success' => $success,
                'php_version' => PHP_VERSION,
                'timestamp' => date('Y-m-d H:i:s')
            ]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'localhost',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'TTS Installer'
        ]);
        
    } catch (Exception $e) {
        // Fail silently if audit logging fails
    }
}

/**
 * Render installation report
 */
function renderInstallationReport() {
    global $results, $errors, $warnings, $startTime;
    
    $duration = round(microtime(true) - $startTime, 2);
    $overallSuccess = empty($errors) && !in_array(false, array_column($results, 'success'));
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TTS PMS Super Admin Installation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            .status-success { color: #28a745; }
            .status-warning { color: #ffc107; }
            .status-error { color: #dc3545; }
            .installation-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        </style>
    </head>
    <body class="bg-light">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header installation-header text-white text-center py-4">
                            <h1 class="mb-0"><i class="fas fa-cogs me-2"></i>TTS PMS Super Admin Installation</h1>
                            <p class="mb-0">Phase 3-5 Integration Complete</p>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($overallSuccess): ?>
                                <div class="alert alert-success text-center">
                                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                                    <h3>Installation Successful!</h3>
                                    <p class="mb-0">All components installed and configured successfully in <?= $duration ?>s</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger text-center">
                                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                    <h3>Installation Issues Detected</h3>
                                    <p class="mb-0">Please review the errors below and retry installation</p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Installation Steps -->
                            <div class="row mt-4">
                                <?php foreach ($results as $step => $result): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <?php if ($result['success']): ?>
                                                        <i class="fas fa-check-circle status-success me-2"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-times-circle status-error me-2"></i>
                                                    <?php endif; ?>
                                                    <?= ucwords(str_replace('_', ' ', $step)) ?>
                                                </h6>
                                                
                                                <?php if (isset($result['passed'], $result['total'])): ?>
                                                    <p class="mb-1"><?= $result['passed'] ?>/<?= $result['total'] ?> checks passed</p>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($result['count'])): ?>
                                                    <p class="mb-1"><?= $result['count'] ?> items processed</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Errors -->
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger mt-4">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Errors</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Warnings -->
                            <?php if (!empty($warnings)): ?>
                                <div class="alert alert-warning mt-4">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Warnings</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($warnings as $warning): ?>
                                            <li><?= htmlspecialchars($warning) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Next Steps -->
                            <?php if ($overallSuccess): ?>
                                <div class="card mt-4 border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-rocket me-2"></i>Next Steps</h6>
                                    </div>
                                    <div class="card-body">
                                        <ol>
                                            <li><strong>Access Admin Panel:</strong> <a href="/admin/" target="_blank">Visit Admin Dashboard</a></li>
                                            <li><strong>Configure Settings:</strong> Use Global Settings Hub to configure SMTP, branding, etc.</li>
                                            <li><strong>Set up Cron Jobs:</strong> Configure automated sync and backup processing</li>
                                            <li><strong>Security:</strong> Remove this installer file after completion</li>
                                            <li><strong>Test Features:</strong> Try Visual Builder, module toggles, and backup system</li>
                                        </ol>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer text-center text-muted">
                            <small>TTS PMS Super Admin Integration v1.0 | Installation completed in <?= $duration ?>s</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
