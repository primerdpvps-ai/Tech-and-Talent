<?php
/**
 * TTS PMS - New Employee Settings
 * Account settings for new employees
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has new_employee role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'new_employee') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'Settings';
$currentPage = 'settings';

// Handle settings update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Verify current password
            $user = $db->fetchOne("SELECT password_hash FROM tts_users WHERE id = ?", [$_SESSION['user_id']]);
            
            if (!password_verify($currentPassword, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 6) {
                $error = 'New password must be at least 6 characters long.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->update('tts_users', ['password_hash' => $hashedPassword], 'id = ?', [$_SESSION['user_id']]);
                $success = 'Password changed successfully!';
            }
        }
        
    } catch (Exception $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}

// Load current user data
try {
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM tts_users WHERE id = ?", [$_SESSION['user_id']]);
} catch (Exception $e) {
    $user = [];
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
            background: linear-gradient(135deg, #fd7e14 0%, #e55353 100%);
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
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-user-graduate me-2"></i>New Employee
            </h4>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
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
            <h2><i class="fas fa-cog me-2"></i>Account Settings</h2>
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

        <!-- Account Information -->
        <div class="settings-section">
            <h4 class="mb-4"><i class="fas fa-user-circle me-2"></i>Account Information</h4>
            
            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>
                <strong>New Employee Status:</strong> You are currently in the onboarding phase. Complete your training and tasks to become a full employee.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><strong>Email Address</strong></label>
                        <div class="form-control-plaintext"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><strong>Status</strong></label>
                        <div>
                            <span class="badge bg-warning">New Employee</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><strong>Start Date</strong></label>
                        <div class="form-control-plaintext">
                            <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><strong>Last Login</strong></label>
                        <div class="form-control-plaintext">
                            <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="settings-section">
            <h4 class="mb-4"><i class="fas fa-lock me-2"></i>Change Password</h4>
            
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-outline mb-4 position-relative">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <label class="form-label" for="current_password">Current Password</label>
                    <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-outline mb-4 position-relative">
                            <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
                            <label class="form-label" for="new_password">New Password</label>
                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-outline mb-4 position-relative">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save me-2"></i>Change Password
                </button>
            </form>
        </div>

        <!-- Onboarding Information -->
        <div class="settings-section">
            <h4 class="mb-4"><i class="fas fa-graduation-cap me-2"></i>Onboarding Information</h4>
            
            <div class="alert alert-info">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Getting Started:</strong> Complete your onboarding tasks and training modules to unlock full employee features.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><strong>Onboarding Phase</strong></label>
                        <div class="form-control-plaintext">Initial Setup & Training</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><strong>Supervisor</strong></label>
                        <div class="form-control-plaintext">HR Department</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <h6>Next Steps:</h6>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check-circle text-success me-2"></i>Complete profile information</li>
                    <li><i class="fas fa-tasks text-warning me-2"></i>Finish onboarding tasks</li>
                    <li><i class="fas fa-graduation-cap text-info me-2"></i>Complete mandatory training</li>
                    <li><i class="fas fa-user-tie text-secondary me-2"></i>Meet with supervisor</li>
                </ul>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-warning">
                    <i class="fas fa-arrow-right me-2"></i>Continue Onboarding
                </a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
