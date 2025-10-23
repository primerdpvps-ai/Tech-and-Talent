<?php
/**
 * TTS PMS Super Admin - Module Control Panel
 * Toggle system modules on/off with dependency checking
 */

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_modules');

$db = Database::getInstance();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    
    try {
        switch ($_POST['action']) {
            case 'toggle_module':
                $moduleName = sanitize_input($_POST['module_name']);
                $isEnabled = (bool)$_POST['is_enabled'];
                $configData = $_POST['config_data'] ?? [];
                
                // Get current state for audit
                $currentConfig = get_module_config($moduleName);
                
                // Check dependencies
                $dependencyIssues = check_module_dependencies($moduleName, $isEnabled ? 'enable' : 'disable');
                if (!empty($dependencyIssues)) {
                    throw new Exception('Dependency issues: ' . implode(', ', $dependencyIssues));
                }
                
                // Update module config
                $success = update_module_config($moduleName, $isEnabled, $configData);
                if (!$success) {
                    throw new Exception('Failed to update module configuration');
                }
                
                // Update navigation menus
                updateModuleNavigation($moduleName, $isEnabled);
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Module $moduleName " . ($isEnabled ? 'enabled' : 'disabled')
                ]);
                break;
                
            case 'bulk_toggle':
                $action = $_POST['bulk_action']; // 'enable_all' or 'disable_all'
                $modules = get_all_modules();
                $results = [];
                
                foreach ($modules as $moduleName => $moduleInfo) {
                    $isEnabled = ($action === 'enable_all');
                    
                    // Skip if dependencies not met
                    if ($isEnabled) {
                        $issues = check_module_dependencies($moduleName, 'enable');
                        if (!empty($issues)) {
                            $results[$moduleName] = ['success' => false, 'error' => 'Dependencies not met'];
                            continue;
                        }
                    }
                    
                    $success = update_module_config($moduleName, $isEnabled);
                    updateModuleNavigation($moduleName, $isEnabled);
                    
                    $results[$moduleName] = ['success' => $success];
                }
                
                echo json_encode(['success' => true, 'results' => $results]);
                break;
                
            case 'update_config':
                $moduleName = sanitize_input($_POST['module_name']);
                $configData = $_POST['config_data'] ?? [];
                
                $currentConfig = get_module_config($moduleName);
                $success = update_module_config($moduleName, $currentConfig['enabled'], $configData);
                
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Configuration updated']);
                } else {
                    throw new Exception('Failed to update configuration');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Get module status and configurations
$modules = get_all_modules();
$moduleConfigs = [];
foreach ($modules as $name => $info) {
    $moduleConfigs[$name] = get_module_config($name);
}

function updateModuleNavigation($moduleName, $isEnabled) {
    // Update dashboard navigation files
    $dashboardDirs = [
        '../packages/web/dashboard/employee/',
        '../packages/web/dashboard/manager/',
        '../packages/web/dashboard/ceo/',
        '../dashboard/employee/',
        '../dashboard/manager/',
        '../dashboard/ceo/'
    ];
    
    $navItems = [
        'payroll' => '<a class="nav-link" href="payroll.php"><i class="fas fa-money-bill-wave me-2"></i>Payroll</a>',
        'training' => '<a class="nav-link" href="training.php"><i class="fas fa-graduation-cap me-2"></i>Training</a>',
        'leave_management' => '<a class="nav-link" href="leaves.php"><i class="fas fa-calendar-times me-2"></i>Leave Requests</a>',
        'evaluations' => '<a class="nav-link" href="evaluation.php"><i class="fas fa-clipboard-check me-2"></i>Evaluation</a>',
        'time_tracking' => '<a class="nav-link" href="timesheet.php"><i class="fas fa-clock me-2"></i>Timesheet</a>',
        'gigs' => '<a class="nav-link" href="gigs.php"><i class="fas fa-briefcase me-2"></i>Gigs</a>'
    ];
    
    if (!isset($navItems[$moduleName])) return;
    
    foreach ($dashboardDirs as $dir) {
        $indexFile = $dir . 'index.php';
        if (file_exists($indexFile)) {
            $content = file_get_contents($indexFile);
            
            if ($isEnabled) {
                // Add navigation item if not present
                if (strpos($content, $navItems[$moduleName]) === false) {
                    // Find navigation section and add item
                    $pattern = '/(<nav[^>]*class="[^"]*nav[^"]*"[^>]*>)(.*?)(<\/nav>)/s';
                    if (preg_match($pattern, $content, $matches)) {
                        $newNav = $matches[1] . $matches[2] . $navItems[$moduleName] . $matches[3];
                        $content = str_replace($matches[0], $newNav, $content);
                        file_put_contents($indexFile, $content);
                    }
                }
            } else {
                // Remove navigation item
                $pattern = '/<a[^>]*href="' . preg_quote(getModuleRoute($moduleName), '/') . '"[^>]*>.*?<\/a>/s';
                $content = preg_replace($pattern, '', $content);
                file_put_contents($indexFile, $content);
            }
        }
    }
}

function getModuleRoute($moduleName) {
    $routes = [
        'payroll' => 'payroll.php',
        'training' => 'training.php',
        'leave_management' => 'leaves.php',
        'evaluations' => 'evaluation.php',
        'time_tracking' => 'timesheet.php',
        'gigs' => 'gigs.php'
    ];
    return $routes[$moduleName] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Module Control Panel - TTS PMS Super Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); transition: all 0.3s ease; border-radius: 8px; margin: 2px 0; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255, 255, 255, 0.1); }
        .module-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 1.5rem; transition: all 0.3s ease; }
        .module-card:hover { transform: translateY(-2px); }
        .module-enabled { border-left: 4px solid #28a745; }
        .module-disabled { border-left: 4px solid #dc3545; }
        .module-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; }
        .module-enabled .module-icon { background: linear-gradient(135deg, #28a745, #20c997); }
        .module-disabled .module-icon { background: linear-gradient(135deg, #6c757d, #495057); }
        .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; }
        input:checked + .slider:before { transform: translateX(26px); }
        .dependency-badge { font-size: 0.75rem; }
        .config-section { background: #f8f9fa; border-radius: 8px; padding: 1rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-puzzle-piece me-2"></i>Modules</h4>
                        <small>System Control</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="system-overview.php">
                            <i class="fas fa-tachometer-alt me-2"></i>System Overview
                        </a>
                        <a class="nav-link" href="page-builder.php">
                            <i class="fas fa-edit me-2"></i>Visual Page Builder
                        </a>
                        <a class="nav-link" href="global-settings.php">
                            <i class="fas fa-cogs me-2"></i>Global Settings
                        </a>
                        <a class="nav-link" href="role-manager.php">
                            <i class="fas fa-users-cog me-2"></i>Role & Permissions
                        </a>
                        <a class="nav-link active" href="module-control.php">
                            <i class="fas fa-puzzle-piece me-2"></i>Module Control
                        </a>
                        <a class="nav-link" href="database-manager.php">
                            <i class="fas fa-database me-2"></i>Database Manager
                        </a>
                        <a class="nav-link" href="audit-log.php">
                            <i class="fas fa-history me-2"></i>Audit Log
                        </a>
                        
                        <hr class="my-3">
                        
                        <a class="nav-link" href="../" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Live Site
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Module Control Panel</h2>
                            <p class="text-muted mb-0">Enable/disable system modules and configure settings</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" onclick="bulkToggle('enable_all')">
                                <i class="fas fa-check-circle me-2"></i>Enable All
                            </button>
                            <button class="btn btn-outline-danger" onclick="bulkToggle('disable_all')">
                                <i class="fas fa-times-circle me-2"></i>Disable All
                            </button>
                        </div>
                    </div>
                    
                    <!-- Module Status Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-success" id="enabledCount">0</h3>
                                    <small class="text-muted">Enabled Modules</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-danger" id="disabledCount">0</h3>
                                    <small class="text-muted">Disabled Modules</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo count($modules); ?></h3>
                                    <small class="text-muted">Total Modules</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-warning" id="dependencyIssues">0</h3>
                                    <small class="text-muted">Dependency Issues</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modules Grid -->
                    <div class="row">
                        <?php foreach ($modules as $moduleName => $moduleInfo): ?>
                        <?php 
                        $config = $moduleConfigs[$moduleName];
                        $isEnabled = $config['enabled'];
                        $dependencyIssues = check_module_dependencies($moduleName, $isEnabled ? 'disable' : 'enable');
                        ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card module-card <?php echo $isEnabled ? 'module-enabled' : 'module-disabled'; ?>" id="module-<?php echo $moduleName; ?>">
                                <div class="card-body">
                                    <div class="d-flex align-items-start">
                                        <div class="module-icon me-3">
                                            <i class="<?php echo $moduleInfo['icon']; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($moduleInfo['name']); ?></h5>
                                                    <p class="card-text text-muted small"><?php echo htmlspecialchars($moduleInfo['description']); ?></p>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" <?php echo $isEnabled ? 'checked' : ''; ?> 
                                                           onchange="toggleModule('<?php echo $moduleName; ?>', this.checked)">
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                            
                                            <!-- Dependencies -->
                                            <?php if (!empty($moduleInfo['dependencies'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">Dependencies:</small>
                                                <?php foreach ($moduleInfo['dependencies'] as $dep): ?>
                                                <span class="badge dependency-badge bg-secondary ms-1"><?php echo $dep; ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Dependency Issues -->
                                            <?php if (!empty($dependencyIssues)): ?>
                                            <div class="mt-2">
                                                <?php foreach ($dependencyIssues as $issue): ?>
                                                <div class="alert alert-warning alert-sm py-1 px-2 small">
                                                    <i class="fas fa-exclamation-triangle me-1"></i><?php echo htmlspecialchars($issue); ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Status & Actions -->
                                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-<?php echo $isEnabled ? 'success' : 'secondary'; ?>">
                                                        <?php echo $isEnabled ? 'Enabled' : 'Disabled'; ?>
                                                    </span>
                                                    <?php if ($isEnabled): ?>
                                                    <small class="text-muted ms-2">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Updated <?php echo date('M j, Y', strtotime($config['config']['updated_at'] ?? 'now')); ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php if ($isEnabled): ?>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="configureModule('<?php echo $moduleName; ?>')">
                                                        <i class="fas fa-cog me-1"></i>Configure
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Configuration Section -->
                                            <div class="config-section" id="config-<?php echo $moduleName; ?>" style="display: none;">
                                                <h6>Module Configuration</h6>
                                                <form id="configForm-<?php echo $moduleName; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                    <input type="hidden" name="module_name" value="<?php echo $moduleName; ?>">
                                                    
                                                    <?php
                                                    // Module-specific configuration options
                                                    switch ($moduleName) {
                                                        case 'payroll':
                                                            echo '<div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="config_data[auto_calculate]" id="payroll_auto" checked>
                                                                <label class="form-check-label" for="payroll_auto">Auto-calculate payroll</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="config_data[approval_required]" id="payroll_approval" checked>
                                                                <label class="form-check-label" for="payroll_approval">Require manager approval</label>
                                                            </div>';
                                                            break;
                                                        case 'training':
                                                            echo '<div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="config_data[auto_assign]" id="training_auto" checked>
                                                                <label class="form-check-label" for="training_auto">Auto-assign training modules</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="config_data[completion_tracking]" id="training_tracking" checked>
                                                                <label class="form-check-label" for="training_tracking">Track completion progress</label>
                                                            </div>';
                                                            break;
                                                        case 'leave_management':
                                                            echo '<div class="mb-2">
                                                                <label class="form-label small">Auto-approval days</label>
                                                                <input type="number" class="form-control form-control-sm" name="config_data[auto_approval_days]" value="2" min="0" max="30">
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="config_data[manager_approval]" id="leave_approval" checked>
                                                                <label class="form-check-label" for="leave_approval">Require manager approval</label>
                                                            </div>';
                                                            break;
                                                        default:
                                                            echo '<div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="config_data[enabled]" checked>
                                                                <label class="form-check-label">Module enabled</label>
                                                            </div>';
                                                    }
                                                    ?>
                                                    
                                                    <div class="mt-3">
                                                        <button type="button" class="btn btn-sm btn-primary" onclick="saveModuleConfig('<?php echo $moduleName; ?>')">
                                                            <i class="fas fa-save me-1"></i>Save Config
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-secondary" onclick="hideConfig('<?php echo $moduleName; ?>')">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCounters();
        });
        
        function updateCounters() {
            const enabledModules = document.querySelectorAll('.module-enabled').length;
            const disabledModules = document.querySelectorAll('.module-disabled').length;
            const dependencyIssues = document.querySelectorAll('.alert-warning').length;
            
            document.getElementById('enabledCount').textContent = enabledModules;
            document.getElementById('disabledCount').textContent = disabledModules;
            document.getElementById('dependencyIssues').textContent = dependencyIssues;
        }
        
        function toggleModule(moduleName, isEnabled) {
            const formData = new FormData();
            formData.append('action', 'toggle_module');
            formData.append('module_name', moduleName);
            formData.append('is_enabled', isEnabled ? '1' : '0');
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('module-control.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const moduleCard = document.getElementById('module-' + moduleName);
                    moduleCard.className = moduleCard.className.replace(/module-(enabled|disabled)/, 'module-' + (isEnabled ? 'enabled' : 'disabled'));
                    
                    const badge = moduleCard.querySelector('.badge');
                    badge.className = 'badge bg-' + (isEnabled ? 'success' : 'secondary');
                    badge.textContent = isEnabled ? 'Enabled' : 'Disabled';
                    
                    const icon = moduleCard.querySelector('.module-icon');
                    icon.className = icon.className.replace(/bg-\w+/, isEnabled ? 'bg-success' : 'bg-secondary');
                    
                    updateCounters();
                    showAlert(data.message, 'success');
                } else {
                    // Revert toggle
                    const toggle = document.querySelector(`#module-${moduleName} input[type="checkbox"]`);
                    toggle.checked = !isEnabled;
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                // Revert toggle
                const toggle = document.querySelector(`#module-${moduleName} input[type="checkbox"]`);
                toggle.checked = !isEnabled;
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function bulkToggle(action) {
            if (!confirm(`Are you sure you want to ${action.replace('_', ' ')} modules?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'bulk_toggle');
            formData.append('bulk_action', action);
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('module-control.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh to show updated states
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function configureModule(moduleName) {
            const configSection = document.getElementById('config-' + moduleName);
            configSection.style.display = configSection.style.display === 'none' ? 'block' : 'none';
        }
        
        function hideConfig(moduleName) {
            document.getElementById('config-' + moduleName).style.display = 'none';
        }
        
        function saveModuleConfig(moduleName) {
            const form = document.getElementById('configForm-' + moduleName);
            const formData = new FormData(form);
            formData.append('action', 'update_config');
            
            fetch('module-control.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hideConfig(moduleName);
                    showAlert(data.message, 'success');
                } else {
                    showAlert('Error: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error: ' + error.message, 'danger');
            });
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
    </script>
</body>
</html>
