<?php
/**
 * TTS PMS Phase 4 - Global Settings Hub
 * Complete system configuration with Branding, SMTP, SEO, Payroll, and Auth
 */

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_settings');

$db = Database::getInstance();

// Get all current settings grouped by category
$allSettings = $db->fetchAll("SELECT setting_key, setting_value, category FROM tts_settings ORDER BY category, setting_key");
$settingsByCategory = [];
foreach ($allSettings as $setting) {
    $settingsByCategory[$setting['category']][$setting['setting_key']] = $setting['setting_value'];
}

// Default values for missing settings
$defaults = [
    'branding' => [
        'site_name' => 'Tech & Talent Solutions',
        'tagline' => 'Precision Data, Global Talent',
        'logo_url' => '',
        'favicon_url' => '',
        'footer_text' => '© 2024 Tech & Talent Solutions. All rights reserved.',
        'social_facebook' => '',
        'social_twitter' => '',
        'social_linkedin' => ''
    ],
    'email' => [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '465',
        'smtp_username' => 'tts.workhub@gmail.com',
        'smtp_password' => 'wcjr uqat kqlz npvd',
        'smtp_encryption' => 'ssl',
        'from_email' => 'tts.workhub@gmail.com',
        'from_name' => 'TTS WorkHub'
    ],
    'seo' => [
        'meta_title' => 'Tech & Talent Solutions - Professional Services',
        'meta_description' => 'Leading provider of professional tech services and workforce management solutions in Pakistan.',
        'canonical_url' => 'https://pms.prizmasoft.com',
        'meta_keywords' => 'data entry, professional services, workforce management',
        'robots_index' => '1',
        'robots_follow' => '1',
        'sitemap_enabled' => '1'
    ],
    'payroll' => [
        'base_hourly_rate' => '125',
        'streak_bonus' => '500',
        'daily_working_hours' => '8',
        'overtime_multiplier' => '1.5',
        'security_fund_rate' => '0.02',
        'holiday_rate_multiplier' => '2.0'
    ],
    'auth' => [
        'gmail_only' => '1',
        'otp_cooldown' => '60',
        'session_timeout' => '3600',
        'max_login_attempts' => '5',
        'require_email_verification' => '1',
        'password_min_length' => '8'
    ]
];

// Merge defaults with existing settings
foreach ($defaults as $category => $categoryDefaults) {
    if (!isset($settingsByCategory[$category])) {
        $settingsByCategory[$category] = [];
    }
    $settingsByCategory[$category] = array_merge($categoryDefaults, $settingsByCategory[$category]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Settings Hub - TTS PMS Super Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); transition: all 0.3s ease; border-radius: 8px; margin: 2px 0; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background-color: rgba(255, 255, 255, 0.1); }
        .settings-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 2rem; }
        .tab-content { padding: 2rem; }
        .form-floating { margin-bottom: 1rem; }
        .test-email-result { margin-top: 1rem; padding: 1rem; border-radius: 8px; }
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
                            <i class="fas fa-edit me-2"></i>Page Manager
                        </a>
                        <a class="nav-link active" href="settings-hub.php">
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
                            <h2 class="mb-1">Global Settings Hub</h2>
                            <p class="text-muted mb-0">Configure system-wide settings and preferences</p>
                        </div>
                        <div>
                            <button class="btn btn-success" onclick="saveAllSettings()">
                                <i class="fas fa-save me-2"></i>Save All Changes
                            </button>
                        </div>
                    </div>
                    
                    <!-- Settings Tabs -->
                    <div class="card settings-card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#branding">
                                        <i class="fas fa-palette me-2"></i>Branding & Appearance
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#email">
                                        <i class="fas fa-envelope me-2"></i>Email & SMTP
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#seo">
                                        <i class="fas fa-search me-2"></i>SEO & Meta
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#payroll">
                                        <i class="fas fa-money-bill-wave me-2"></i>Payroll & HR
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#auth">
                                        <i class="fas fa-shield-alt me-2"></i>Security & Auth
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="tab-content">
                            <!-- Branding Tab -->
                            <div class="tab-pane fade show active" id="branding">
                                <h5 class="mb-4">Branding & Appearance Settings</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="site_name" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['branding']['site_name']); ?>">
                                            <label>Site Name</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="tagline" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['branding']['tagline']); ?>">
                                            <label>Tagline</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="logo_url" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['branding']['logo_url']); ?>">
                                            <label>Logo URL</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="favicon_url" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['branding']['favicon_url']); ?>">
                                            <label>Favicon URL</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="footer_text" style="height: 80px"><?php echo htmlspecialchars($settingsByCategory['branding']['footer_text']); ?></textarea>
                                            <label>Footer Text</label>
                                        </div>
                                        
                                        <h6 class="mt-3 mb-3">Social Media Links</h6>
                                        
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="social_facebook" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['branding']['social_facebook']); ?>">
                                            <label>Facebook URL</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="social_linkedin" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['branding']['social_linkedin']); ?>">
                                            <label>LinkedIn URL</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Email Tab -->
                            <div class="tab-pane fade" id="email">
                                <h5 class="mb-4">Email & SMTP Configuration</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="smtp_host" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['email']['smtp_host']); ?>">
                                            <label>SMTP Host</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="smtp_port" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['email']['smtp_port']); ?>">
                                            <label>SMTP Port</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <select class="form-select" id="smtp_encryption">
                                                <option value="ssl" <?php echo $settingsByCategory['email']['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="tls" <?php echo $settingsByCategory['email']['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="none" <?php echo $settingsByCategory['email']['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                            </select>
                                            <label>Encryption</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="smtp_username" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['email']['smtp_username']); ?>">
                                            <label>SMTP Username</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="password" class="form-control" id="smtp_password" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['email']['smtp_password']); ?>">
                                            <label>SMTP Password</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="from_email" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['email']['from_email']); ?>">
                                            <label>From Email</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="from_name" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['email']['from_name']); ?>">
                                            <label>From Name</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="btn btn-outline-primary" onclick="testEmail()">
                                        <i class="fas fa-paper-plane me-2"></i>Send Test Email
                                    </button>
                                    <div id="testEmailResult" class="test-email-result" style="display: none;"></div>
                                </div>
                            </div>
                            
                            <!-- SEO Tab -->
                            <div class="tab-pane fade" id="seo">
                                <h5 class="mb-4">SEO & Meta Configuration</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="meta_title" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['seo']['meta_title']); ?>">
                                            <label>Default Meta Title</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <textarea class="form-control" id="meta_description" style="height: 100px"><?php echo htmlspecialchars($settingsByCategory['seo']['meta_description']); ?></textarea>
                                            <label>Default Meta Description</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="url" class="form-control" id="canonical_url" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['seo']['canonical_url']); ?>">
                                            <label>Canonical URL</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <textarea class="form-control" id="meta_keywords" style="height: 80px"><?php echo htmlspecialchars($settingsByCategory['seo']['meta_keywords']); ?></textarea>
                                            <label>Meta Keywords (comma-separated)</label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="robots_index" 
                                                   <?php echo $settingsByCategory['seo']['robots_index'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Allow Search Engine Indexing</label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="robots_follow" 
                                                   <?php echo $settingsByCategory['seo']['robots_follow'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Allow Following Links</label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="sitemap_enabled" 
                                                   <?php echo $settingsByCategory['seo']['sitemap_enabled'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Enable XML Sitemap</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payroll Tab -->
                            <div class="tab-pane fade" id="payroll">
                                <h5 class="mb-4">Payroll & HR Configuration</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="base_hourly_rate" step="0.01" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['payroll']['base_hourly_rate']); ?>">
                                            <label>Base Hourly Rate (PKR)</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="streak_bonus" step="0.01" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['payroll']['streak_bonus']); ?>">
                                            <label>28-Day Streak Bonus (PKR)</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="daily_working_hours" min="1" max="24" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['payroll']['daily_working_hours']); ?>">
                                            <label>Daily Working Hours</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="overtime_multiplier" step="0.1" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['payroll']['overtime_multiplier']); ?>">
                                            <label>Overtime Rate Multiplier</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="security_fund_rate" step="0.001" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['payroll']['security_fund_rate']); ?>">
                                            <label>Security Fund Rate (%)</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="holiday_rate_multiplier" step="0.1" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['payroll']['holiday_rate_multiplier']); ?>">
                                            <label>Holiday Rate Multiplier</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Auth Tab -->
                            <div class="tab-pane fade" id="auth">
                                <h5 class="mb-4">Security & Authentication Rules</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="gmail_only" 
                                                   <?php echo $settingsByCategory['auth']['gmail_only'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Require Gmail Addresses Only</label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="require_email_verification" 
                                                   <?php echo $settingsByCategory['auth']['require_email_verification'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Require Email Verification</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="otp_cooldown" min="30" max="300" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['auth']['otp_cooldown']); ?>">
                                            <label>OTP Cooldown (seconds)</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="session_timeout" min="300" max="86400" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['auth']['session_timeout']); ?>">
                                            <label>Session Timeout (seconds)</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="max_login_attempts" min="3" max="10" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['auth']['max_login_attempts']); ?>">
                                            <label>Max Login Attempts</label>
                                        </div>
                                        
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="password_min_length" min="6" max="20" 
                                                   value="<?php echo htmlspecialchars($settingsByCategory['auth']['password_min_length']); ?>">
                                            <label>Minimum Password Length</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function saveAllSettings() {
            const categories = ['branding', 'email', 'seo', 'payroll', 'auth'];
            let allPromises = [];
            
            categories.forEach(category => {
                const settings = getSettingsForCategory(category);
                const promise = saveSettings(category, settings);
                allPromises.push(promise);
            });
            
            Promise.all(allPromises)
                .then(() => {
                    showAlert('All settings saved successfully!', 'success');
                })
                .catch(error => {
                    showAlert('Error saving settings: ' + error.message, 'danger');
                });
        }
        
        function getSettingsForCategory(category) {
            const settings = {};
            const tabPane = document.getElementById(category);
            
            tabPane.querySelectorAll('input, select, textarea').forEach(input => {
                if (input.type === 'checkbox') {
                    settings[input.id] = input.checked ? '1' : '0';
                } else {
                    settings[input.id] = input.value;
                }
            });
            
            return settings;
        }
        
        function saveSettings(category, settings) {
            const formData = new FormData();
            formData.append('category', category);
            formData.append('settings', JSON.stringify(settings));
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            return fetch('api/save-setting.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error);
                }
                return data;
            });
        }
        
        function testEmail() {
            const emailSettings = getSettingsForCategory('email');
            
            const formData = new FormData();
            formData.append('action', 'test_email');
            formData.append('settings', JSON.stringify(emailSettings));
            formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
            
            fetch('api/test-email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('testEmailResult');
                resultDiv.style.display = 'block';
                
                if (data.success) {
                    resultDiv.className = 'test-email-result bg-success text-white';
                    resultDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>Test email sent successfully!';
                } else {
                    resultDiv.className = 'test-email-result bg-danger text-white';
                    resultDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: ' + data.error;
                }
            })
            .catch(error => {
                const resultDiv = document.getElementById('testEmailResult');
                resultDiv.style.display = 'block';
                resultDiv.className = 'test-email-result bg-danger text-white';
                resultDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Error: ' + error.message;
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
        
        // Auto-save on input change
        document.addEventListener('input', function(e) {
            if (e.target.matches('input, select, textarea')) {
                // Debounced auto-save could be implemented here
            }
        });
    </script>
</body>
</html>
