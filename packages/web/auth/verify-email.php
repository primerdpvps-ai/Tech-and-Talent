<?php
/**
 * TTS PMS - Email Verification Handler
 * Handles email verification tokens and activates accounts
 */

// Load configuration
require_once '../../../config/init.php';
require_once '../../../config/email_config.php';

// Start session
session_start();

$message = '';
$messageType = '';
$verified = false;

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid verification link. Please check your email for the correct link.';
    $messageType = 'error';
} else {
    try {
        $db = Database::getInstance();
        
        // Find user with this verification token
        $user = $db->fetchOne(
            'SELECT id, email, first_name, last_name, verification_token, token_expiry, status 
             FROM tts_users 
             WHERE verification_token = ? AND status = ?',
            [$token, 'PENDING_VERIFICATION']
        );
        
        if (!$user) {
            $message = 'Invalid or expired verification link. Please try registering again.';
            $messageType = 'error';
        } else {
            // Check if token has expired
            $currentTime = new DateTime();
            $expiryTime = new DateTime($user['token_expiry']);
            
            if ($currentTime > $expiryTime) {
                $message = 'Verification link has expired. Please register again to receive a new verification email.';
                $messageType = 'error';
                
                // Optionally delete expired user record
                $db->query('DELETE FROM tts_users WHERE id = ?', [$user['id']]);
            } else {
                // Verify the account
                $updateData = [
                    'status' => 'ACTIVE',
                    'email_verified' => true,
                    'verification_token' => null,
                    'token_expiry' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $updated = $db->update('tts_users', $updateData, 'id = ?', [$user['id']]);
                
                if ($updated) {
                    // Send welcome email
                    sendWelcomeEmail($user['email'], $user['first_name']);
                    
                    $message = 'Email verified successfully! Your account is now active. You can sign in to start using TTS WorkHub.';
                    $messageType = 'success';
                    $verified = true;
                    
                    // Store user info for auto-login option
                    $_SESSION['verified_email'] = $user['email'];
                    $_SESSION['verified_name'] = $user['first_name'] . ' ' . $user['last_name'];
                } else {
                    $message = 'Verification failed. Please try again or contact support.';
                    $messageType = 'error';
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        $message = 'An error occurred during verification. Please try again or contact support.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - TTS WorkHub</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .verification-container {
            width: 100%;
            max-width: 500px;
        }
        
        .verification-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .verification-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 4rem;
            color: white;
        }
        
        .verification-icon.success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
        }
        
        .verification-icon.error {
            background: linear-gradient(135deg, var(--danger-color), #e74c3c);
        }
        
        .verification-icon.loading {
            background: linear-gradient(135deg, var(--info-color), #3498db);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .btn-action {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-outline-action {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 10px;
            padding: 10px 30px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-outline-action:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .alert-custom.success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .alert-custom.error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(231, 76, 60, 0.1));
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .welcome-message {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(32, 201, 151, 0.05));
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .feature-list {
            text-align: left;
            margin: 1.5rem 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .verification-card {
                padding: 2rem 1.5rem;
            }
            
            .verification-icon {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .verification-card {
                padding: 1.5rem 1rem;
            }
            
            .verification-icon {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }
            
            .btn-action, .btn-outline-action {
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-card">
            <?php if ($verified): ?>
            <!-- Success State -->
            <div class="verification-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="mb-3 text-success">Email Verified Successfully!</h1>
            
            <div class="welcome-message">
                <h4 class="text-success mb-3">
                    🎉 Welcome to TTS WorkHub, <?php echo htmlspecialchars($_SESSION['verified_name'] ?? ''); ?>!
                </h4>
                <p class="mb-0">Your account is now active and ready to use.</p>
            </div>
            
            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <strong>Complete Your Profile</strong>
                        <div class="text-muted small">Fill out your profile information to get started</div>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div>
                        <strong>Take the Evaluation</strong>
                        <div class="text-muted small">Complete our skills assessment to qualify for projects</div>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <strong>Start Earning</strong>
                        <div class="text-muted small">Begin working on data entry projects and get paid</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="sign-in.php" class="btn-action">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In to Your Account
                </a>
                <a href="../../../" class="btn-outline-action">
                    <i class="fas fa-home me-2"></i>Visit Homepage
                </a>
            </div>
            
            <?php else: ?>
            <!-- Error State -->
            <div class="verification-icon error">
                <i class="fas fa-times-circle"></i>
            </div>
            
            <h1 class="mb-3 text-danger">Verification Failed</h1>
            
            <div class="alert-custom error">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <div class="mt-4">
                <a href="sign-up-new.php" class="btn-action">
                    <i class="fas fa-user-plus me-2"></i>Register Again
                </a>
                <a href="sign-in.php" class="btn-outline-action">
                    <i class="fas fa-sign-in-alt me-2"></i>Try Signing In
                </a>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    <small>
                        Need help? Contact our support team at 
                        <a href="mailto:support@tts-workhub.com">support@tts-workhub.com</a>
                    </small>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-redirect to sign-in after successful verification
        <?php if ($verified): ?>
        setTimeout(function() {
            if (confirm('Would you like to sign in now?')) {
                window.location.href = 'sign-in.php';
            }
        }, 3000);
        <?php endif; ?>
        
        // Add some interactive feedback
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('.btn-action, .btn-outline-action');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html>
