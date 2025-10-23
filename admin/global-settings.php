<?php
/**
 * TTS PMS Super Admin - Global Settings Panel
 * Complete system configuration control
 */

require_once '../config/init.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_settings':
                $category = $_POST['category'] ?? '';
                $settings = $_POST['settings'] ?? [];
                
                foreach ($settings as $key => $value) {
                    $db->query(
                        "INSERT INTO tts_settings (setting_key, setting_value, category) VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                        [$key, $value, $category]
                    );
                }
                
                // Log the change
                $db->insert('tts_admin_edits', [
                    'admin_id' => $_SESSION['user_id'] ?? 1,
                    'action_type' => 'settings_update',
                    'target_table' => 'tts_settings',
                    'changes' => json_encode($settings),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
                break;
                
            case 'get_settings':
                $category = $_POST['category'] ?? '';
                $result = $db->query("SELECT setting_key, setting_value FROM tts_settings WHERE category = ?", [$category]);
                $settings = [];
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
                echo json_encode(['success' => true, 'settings' => $settings]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get current settings
function getSettings($category) {
    global $db;
    try {
        $result = $db->query("SELECT setting_key, setting_value FROM tts_settings WHERE category = ?", [$category]);
        $settings = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

$payrollSettings = getSettings('payroll');
$emailSettings = getSettings('email');
$brandingSettings = getSettings('branding');
$seoSettings = getSettings('seo');
$authSettings = getSettings('auth');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Settings - TTS PMS Super Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .settings-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-cogs me-2"></i>Settings</h4>
                        <small>Global Configuration</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="system-overview.php">
                            <i class="fas fa-tachometer-alt me-2"></i>System Overview
                        </a>
                        <a class="nav-link" href="page-builder.php">
                            <i class="fas fa-edit me-2"></i>Visual Page Builder
                        </a>
                        <a class="nav-link active" href="global-settings.php">
                            <i class="fas fa-cogs me-2"></i>Global Settings
                        </a>
                        <a class="nav-link" href="role-manager.php">
                            <i class="fas fa-users-cog me-2"></i>Role & Permissions
                        </a>
                        <a class="nav-link" href="module-control.php">
                            <i class="fas fa-puzzle-piece me-2"></i>Module Control
                        </a>
                        <a class="nav-link" href="database-manager.php">
                            <i class="fas fa-database me-2"></i>Database Manager
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
                            <h2 class="mb-1">Global Settings</h2>
                            <p class="text-muted mb-0">Configure system-wide settings and preferences</p>
                        </div>
                        <button class="btn btn-outline-primary" onclick="exportSettings()">
                            <i class="fas fa-download me-1"></i>Export Config
                        </button>
                    </div>
                    
                    <!-- Payroll Settings -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h4 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payroll Configuration</h4>
                        </div>
                        <div class="card-body p-4">
                            <form id="payrollForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="baseHourlyRate" name="base_hourly_rate" 
                                                   value="<?php echo htmlspecialchars($payrollSettings['base_hourly_rate'] ?? '125'); ?>">
                                            <label>Base Hourly Rate (PKR)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="streakBonus" name="streak_bonus" 
                                                   value="<?php echo htmlspecialchars($payrollSettings['streak_bonus'] ?? '500'); ?>">
                                            <label>28-Day Streak Bonus (PKR)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="workingHours" name="daily_working_hours" 
                                                   value="<?php echo htmlspecialchars($payrollSettings['daily_working_hours'] ?? '8'); ?>">
                                            <label>Daily Working Hours</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="overtimeRate" name="overtime_multiplier" 
                                                   value="<?php echo htmlspecialchars($payrollSettings['overtime_multiplier'] ?? '1.5'); ?>" step="0.1">
                                            <label>Overtime Multiplier</label>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-save text-white" onclick="saveSettings('payroll')">
                                    <i class="fas fa-save me-2"></i>Save Payroll Settings
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Email Settings -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>SMTP Email Configuration</h4>
                        </div>
                        <div class="card-body p-4">
                            <form id="emailForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="smtpHost" name="smtp_host" 
                                                   value="<?php echo htmlspecialchars($emailSettings['smtp_host'] ?? 'smtp.gmail.com'); ?>">
                                            <label>SMTP Host</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="smtpPort" name="smtp_port" 
                                                   value="<?php echo htmlspecialchars($emailSettings['smtp_port'] ?? '465'); ?>">
                                            <label>SMTP Port</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="smtpUsername" name="smtp_username" 
                                                   value="<?php echo htmlspecialchars($emailSettings['smtp_username'] ?? 'tts.workhub@gmail.com'); ?>">
                                            <label>SMTP Username</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="smtpPassword" name="smtp_password" 
                                                   value="<?php echo htmlspecialchars($emailSettings['smtp_password'] ?? ''); ?>">
                                            <label>SMTP Password</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="smtpEncryption" name="smtp_encryption">
                                                <option value="ssl" <?php echo ($emailSettings['smtp_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="tls" <?php echo ($emailSettings['smtp_encryption'] ?? 'ssl') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            </select>
                                            <label>Encryption</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="fromEmail" name="from_email" 
                                                   value="<?php echo htmlspecialchars($emailSettings['from_email'] ?? 'tts.workhub@gmail.com'); ?>">
                                            <label>From Email</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-save text-white" onclick="saveSettings('email')">
                                        <i class="fas fa-save me-2"></i>Save Email Settings
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="testEmail()">
                                        <i class="fas fa-paper-plane me-2"></i>Test Email
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Branding Settings -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h4 class="mb-0"><i class="fas fa-palette me-2"></i>Branding & Appearance</h4>
                        </div>
                        <div class="card-body p-4">
                            <form id="brandingForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="siteName" name="site_name" 
                                                   value="<?php echo htmlspecialchars($brandingSettings['site_name'] ?? 'Tech & Talent Solutions'); ?>">
                                            <label>Site Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="tagline" name="tagline" 
                                                   value="<?php echo htmlspecialchars($brandingSettings['tagline'] ?? 'Precision Data, Global Talent'); ?>">
                                            <label>Tagline</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="logoUrl" name="logo_url" 
                                                   value="<?php echo htmlspecialchars($brandingSettings['logo_url'] ?? ''); ?>">
                                            <label>Logo URL</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="faviconUrl" name="favicon_url" 
                                                   value="<?php echo htmlspecialchars($brandingSettings['favicon_url'] ?? ''); ?>">
                                            <label>Favicon URL</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="footerText" name="footer_text" style="height: 100px"><?php echo htmlspecialchars($brandingSettings['footer_text'] ?? '© 2024 Tech & Talent Solutions. All rights reserved.'); ?></textarea>
                                            <label>Footer Text</label>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-save text-white" onclick="saveSettings('branding')">
                                    <i class="fas fa-save me-2"></i>Save Branding Settings
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- SEO Settings -->
                    <div class="settings-card">
                        <div class="settings-header">
                            <h4 class="mb-0"><i class="fas fa-search me-2"></i>SEO Configuration</h4>
                        </div>
                        <div class="card-body p-4">
                            <form id="seoForm">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="metaTitle" name="meta_title" 
                                                   value="<?php echo htmlspecialchars($seoSettings['meta_title'] ?? 'Tech & Talent Solutions - Professional Services'); ?>">
                                            <label>Default Meta Title</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="metaDescription" name="meta_description" style="height: 100px"><?php echo htmlspecialchars($seoSettings['meta_description'] ?? 'Leading provider of professional tech services and workforce management solutions in Pakistan.'); ?></textarea>
                                            <label>Default Meta Description</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="canonicalUrl" name="canonical_url" 
                                                   value="<?php echo htmlspecialchars($seoSettings['canonical_url'] ?? 'https://pms.prizmasoft.com'); ?>">
                                            <label>Canonical URL</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="keywords" name="meta_keywords" 
                                                   value="<?php echo htmlspecialchars($seoSettings['meta_keywords'] ?? 'data entry, professional services, workforce management'); ?>">
                                            <label>Meta Keywords</label>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-save text-white" onclick="saveSettings('seo')">
                                    <i class="fas fa-save me-2"></i>Save SEO Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function saveSettings(category) {
            const form = document.getElementById(category + 'Form');
            const formData = new FormData(form);
            const settings = {};
            
            for (let [key, value] of formData.entries()) {
                settings[key] = value;
            }
            
            fetch('global-settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_settings&category=${category}&settings=${encodeURIComponent(JSON.stringify(settings))}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Settings saved successfully!', 'success');
                } else {
                    showAlert('Error saving settings: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                showAlert('Error saving settings: ' + error.message, 'danger');
            });
        }
        
        function testEmail() {
            const email = prompt('Enter email address to test:');
            if (email) {
                window.open(`../test-email.php?email=${encodeURIComponent(email)}`, '_blank');
            }
        }
        
        function exportSettings() {
            window.location.href = 'export-config.php';
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
