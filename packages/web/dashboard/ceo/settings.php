<?php
/**
 * TTS PMS - CEO Settings
 * System configuration and company settings management
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has CEO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ceo') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'CEO Settings';
$currentPage = 'settings';

// Handle settings update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        // Update company settings
        $settings = [
            'company_name' => $_POST['company_name'] ?? '',
            'company_email' => $_POST['company_email'] ?? '',
            'company_phone' => $_POST['company_phone'] ?? '',
            'hourly_rate' => $_POST['hourly_rate'] ?? '',
            'streak_bonus' => $_POST['streak_bonus'] ?? '',
            'security_fund' => $_POST['security_fund'] ?? '',
            'operational_start' => $_POST['operational_start'] ?? '',
            'operational_end' => $_POST['operational_end'] ?? '',
            'min_daily_hours_ft' => $_POST['min_daily_hours_ft'] ?? '',
            'max_daily_hours_ft' => $_POST['max_daily_hours_ft'] ?? ''
        ];
        
        foreach ($settings as $key => $value) {
            $db->query(
                "INSERT INTO tts_settings (setting_key, setting_value, category) VALUES (?, ?, 'general') 
                 ON DUPLICATE KEY UPDATE setting_value = ?",
                [$key, $value, $value]
            );
        }
        
        $success = 'Settings updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Load current settings
try {
    $db = Database::getInstance();
    $currentSettings = [];
    $settings = $db->fetchAll("SELECT setting_key, setting_value FROM tts_settings");
    foreach ($settings as $setting) {
        $currentSettings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $currentSettings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TTS PMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- MDB UI Kit -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .settings-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-crown me-2"></i>CEO Panel
            </h4>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="../../../../admin/dashboard.php">
                        <i class="fas fa-shield-alt me-2"></i>Admin Panel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../auth/logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-cog me-2"></i>CEO Settings</h2>
            <div class="text-muted">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Company Information -->
            <div class="settings-section">
                <h4 class="mb-4"><i class="fas fa-building me-2"></i>Company Information</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="text" id="company_name" name="company_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_name'] ?? 'Tech & Talent Solutions Ltd.'); ?>">
                            <label class="form-label" for="company_name">Company Name</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="email" id="company_email" name="company_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['company_email'] ?? 'info@tts.com.pk'); ?>">
                            <label class="form-label" for="company_email">Company Email</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-outline mb-4">
                    <input type="tel" id="company_phone" name="company_phone" class="form-control" 
                           value="<?php echo htmlspecialchars($currentSettings['company_phone'] ?? '+92 300 1234567'); ?>">
                    <label class="form-label" for="company_phone">Company Phone</label>
                </div>
            </div>

            <!-- Payroll Settings -->
            <div class="settings-section">
                <h4 class="mb-4"><i class="fas fa-money-bill-wave me-2"></i>Payroll Settings</h4>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-outline mb-4">
                            <input type="number" id="hourly_rate" name="hourly_rate" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['hourly_rate'] ?? '125'); ?>">
                            <label class="form-label" for="hourly_rate">Hourly Rate (PKR)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-outline mb-4">
                            <input type="number" id="streak_bonus" name="streak_bonus" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['streak_bonus'] ?? '500'); ?>">
                            <label class="form-label" for="streak_bonus">Streak Bonus (PKR)</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-outline mb-4">
                            <input type="number" id="security_fund" name="security_fund" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['security_fund'] ?? '1000'); ?>">
                            <label class="form-label" for="security_fund">Security Fund (PKR)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operational Hours -->
            <div class="settings-section">
                <h4 class="mb-4"><i class="fas fa-clock me-2"></i>Operational Hours</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="time" id="operational_start" name="operational_start" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['operational_start'] ?? '11:00'); ?>">
                            <label class="form-label" for="operational_start">Start Time (PKT)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="time" id="operational_end" name="operational_end" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['operational_end'] ?? '02:00'); ?>">
                            <label class="form-label" for="operational_end">End Time (PKT)</label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="number" id="min_daily_hours_ft" name="min_daily_hours_ft" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['min_daily_hours_ft'] ?? '6'); ?>">
                            <label class="form-label" for="min_daily_hours_ft">Min Daily Hours (Full-time)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-outline mb-4">
                            <input type="number" id="max_daily_hours_ft" name="max_daily_hours_ft" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentSettings['max_daily_hours_ft'] ?? '8'); ?>">
                            <label class="form-label" for="max_daily_hours_ft">Max Daily Hours (Full-time)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
</body>
</html>
