<?php
/**
 * TTS PMS - Forgot Password
 * Password reset functionality
 */

// Load configuration
require_once '../../../config/init.php';

// Start session
session_start();

$message = '';
$messageType = '';
$step = $_GET['step'] ?? 'email';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_reset'])) {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $message = 'Please enter your email address.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'danger';
        } else {
            try {
                $db = Database::getInstance();
                $user = $db->fetchOne('SELECT * FROM tts_users WHERE email = ?', [$email]);
                
                if ($user) {
                    // Generate reset token
                    $resetToken = bin2hex(random_bytes(32));
                    $resetExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store reset token (in real implementation, save to database)
                    $_SESSION['reset_token'] = $resetToken;
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_expiry'] = $resetExpiry;
                    
                    // In real implementation, send email here
                    $message = 'Password reset instructions have been sent to your email address.';
                    $messageType = 'success';
                    $step = 'sent';
                } else {
                    $message = 'No account found with that email address.';
                    $messageType = 'danger';
                }
            } catch (Exception $e) {
                $message = 'An error occurred. Please try again later.';
                $messageType = 'danger';
                error_log('Forgot password error: ' . $e->getMessage());
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $token = trim($_POST['token'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'danger';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'danger';
        } elseif (strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $messageType = 'danger';
        } else {
            // Verify token (in real implementation, check database)
            if (isset($_SESSION['reset_token']) && 
                hash_equals($_SESSION['reset_token'], $token) &&
                isset($_SESSION['reset_expiry']) &&
                strtotime($_SESSION['reset_expiry']) > time()) {
                
                try {
                    $db = Database::getInstance();
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    $updated = $db->update(
                        'tts_users',
                        ['password_hash' => $hashedPassword],
                        'email = ?',
                        [$_SESSION['reset_email']]
                    );
                    
                    if ($updated) {
                        // Clear reset session data
                        unset($_SESSION['reset_token'], $_SESSION['reset_email'], $_SESSION['reset_expiry']);
                        
                        $message = 'Your password has been successfully reset. You can now sign in with your new password.';
                        $messageType = 'success';
                        $step = 'complete';
                    } else {
                        $message = 'Failed to update password. Please try again.';
                        $messageType = 'danger';
                    }
                } catch (Exception $e) {
                    $message = 'An error occurred. Please try again later.';
                    $messageType = 'danger';
                    error_log('Password reset error: ' . $e->getMessage());
                }
            } else {
                $message = 'Invalid or expired reset token. Please request a new password reset.';
                $messageType = 'danger';
                $step = 'email';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TTS PMS</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .auth-header {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            color: white;
        }
        
        .step.completed {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .form-outline {
            margin-bottom: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(18, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="auth-header">
                        <i class="fas fa-key fa-2x mb-3"></i>
                        <h3 class="mb-0">Reset Password</h3>
                        <p class="mb-0 opacity-75">Recover access to your account</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step <?php echo $step === 'email' ? 'active' : ($step === 'sent' || $step === 'reset' || $step === 'complete' ? 'completed' : 'inactive'); ?>">1</div>
                            <div class="step <?php echo $step === 'sent' ? 'active' : ($step === 'reset' || $step === 'complete' ? 'completed' : 'inactive'); ?>">2</div>
                            <div class="step <?php echo $step === 'reset' ? 'active' : ($step === 'complete' ? 'completed' : 'inactive'); ?>">3</div>
                            <div class="step <?php echo $step === 'complete' ? 'active' : 'inactive'; ?>">4</div>
                        </div>
                        
                        <!-- Alert Messages -->
                        <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($step === 'email'): ?>
                        <!-- Step 1: Enter Email -->
                        <h5 class="mb-3">Enter Your Email</h5>
                        <p class="text-muted mb-4">Enter the email address associated with your account and we'll send you instructions to reset your password.</p>
                        
                        <form method="POST">
                            <div class="form-outline">
                                <input type="email" id="email" name="email" class="form-control" required>
                                <label class="form-label" for="email">Email Address</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="send_reset" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                                </button>
                            </div>
                        </form>
                        
                        <?php elseif ($step === 'sent'): ?>
                        <!-- Step 2: Instructions Sent -->
                        <div class="text-center">
                            <i class="fas fa-envelope-open fa-3x text-success mb-3"></i>
                            <h5 class="mb-3">Check Your Email</h5>
                            <p class="text-muted mb-4">We've sent password reset instructions to your email address. Please check your inbox and follow the link to reset your password.</p>
                            
                            <div class="alert alert-info">
                                <strong>Demo Mode:</strong> Use token: <code><?php echo $_SESSION['reset_token'] ?? 'N/A'; ?></code>
                            </div>
                            
                            <a href="?step=reset" class="btn btn-primary">
                                <i class="fas fa-arrow-right me-2"></i>Continue to Reset
                            </a>
                        </div>
                        
                        <?php elseif ($step === 'reset'): ?>
                        <!-- Step 3: Reset Password -->
                        <h5 class="mb-3">Create New Password</h5>
                        <p class="text-muted mb-4">Enter your new password below. Make sure it's strong and secure.</p>
                        
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo $_SESSION['reset_token'] ?? ''; ?>">
                            
                            <div class="form-outline">
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                                <label class="form-label" for="new_password">New Password</label>
                            </div>
                            
                            <div class="form-outline">
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="reset_password" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>Reset Password
                                </button>
                            </div>
                        </form>
                        
                        <?php else: ?>
                        <!-- Step 4: Complete -->
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="mb-3">Password Reset Complete</h5>
                            <p class="text-muted mb-4">Your password has been successfully reset. You can now sign in with your new password.</p>
                            
                            <a href="sign-in.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In Now
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Remember your password? 
                                <a href="sign-in.php" class="text-decoration-none">Sign In</a>
                            </p>
                            <p class="mb-0 mt-2">
                                <a href="../../../index.php" class="text-muted text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Back to Main Website
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
</body>
</html>
