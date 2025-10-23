<?php
require_once '../config/init.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'action') {
                $db->query("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $value, $value]);
            }
        }
        $message = 'Settings updated successfully!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$settings = [];
$settingsData = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings");
foreach ($settingsData as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #1266f1, #39c0ed); min-height: 100vh; width: 250px; position: fixed; }
        .main-content { margin-left: 250px; padding: 20px; }
        .settings-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        @media (max-width: 768px) { .sidebar { margin-left: -250px; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4"><i class="fas fa-shield-alt me-2"></i>TTS Admin</h4>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link text-white mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="applications.php" class="nav-link text-white mb-2"><i class="fas fa-file-alt me-2"></i>Applications</a>
                <a href="employees.php" class="nav-link text-white mb-2"><i class="fas fa-users me-2"></i>Employees</a>
                <a href="payroll-automation.php" class="nav-link text-white mb-2"><i class="fas fa-money-bill-wave me-2"></i>Payroll</a>
                <a href="leaves.php" class="nav-link text-white mb-2"><i class="fas fa-calendar-times me-2"></i>Leaves</a>
                <a href="clients.php" class="nav-link text-white mb-2"><i class="fas fa-handshake me-2"></i>Clients</a>
                <a href="proposals.php" class="nav-link text-white mb-2"><i class="fas fa-file-contract me-2"></i>Proposals</a>
                <a href="gigs.php" class="nav-link text-white mb-2"><i class="fas fa-briefcase me-2"></i>Gigs</a>
                <a href="reports.php" class="nav-link text-white mb-2"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                <a href="settings.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded"><i class="fas fa-cog me-2"></i>Settings</a>
                <hr class="text-white">
                <a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </nav>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">System Settings</h2>
                <p class="text-muted mb-0">Configure system-wide settings and preferences</p>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Company Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" name="company_name" 
                                       value="<?php echo htmlspecialchars($settings['company_name'] ?? 'Tech & Talent Solutions Ltd.'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company Email</label>
                                <input type="email" class="form-control" name="company_email" 
                                       value="<?php echo htmlspecialchars($settings['company_email'] ?? 'info@tts.com.pk'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company Phone</label>
                                <input type="tel" class="form-control" name="company_phone" 
                                       value="<?php echo htmlspecialchars($settings['company_phone'] ?? '+92 300 1234567'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company Address</label>
                                <textarea class="form-control" name="company_address" rows="3"><?php echo htmlspecialchars($settings['company_address'] ?? 'Lahore, Pakistan'); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payroll Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Base Hourly Rate (PKR)</label>
                                <input type="number" class="form-control" name="base_hourly_rate" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($settings['base_hourly_rate'] ?? '125'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Streak Bonus (PKR)</label>
                                <input type="number" class="form-control" name="streak_bonus" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($settings['streak_bonus'] ?? '500'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Minimum Daily Hours</label>
                                <input type="number" class="form-control" name="min_daily_hours" step="0.1" min="0"
                                       value="<?php echo htmlspecialchars($settings['min_daily_hours'] ?? '8'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Streak Threshold (Days)</label>
                                <input type="number" class="form-control" name="streak_threshold" min="1"
                                       value="<?php echo htmlspecialchars($settings['streak_threshold'] ?? '28'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" 
                                       value="<?php echo htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port" 
                                       value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" name="smtp_username" 
                                       value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" name="smtp_password" 
                                       value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>System Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Timezone</label>
                                <select class="form-select" name="timezone">
                                    <option value="Asia/Karachi" <?php echo ($settings['timezone'] ?? 'Asia/Karachi') === 'Asia/Karachi' ? 'selected' : ''; ?>>Asia/Karachi</option>
                                    <option value="UTC" <?php echo ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <select class="form-select" name="currency">
                                    <option value="PKR" <?php echo ($settings['currency'] ?? 'PKR') === 'PKR' ? 'selected' : ''; ?>>PKR - Pakistani Rupee</option>
                                    <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" 
                                       <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Maintenance Mode</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="registration_enabled" value="1" 
                                       <?php echo ($settings['registration_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label">Allow New Registrations</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
</body>
</html>
