<?php
/**
 * TTS PMS - User Registration Redirect
 * Redirects to new email-only registration system
 */

// Redirect to new sign-up page with email verification only
header('Location: sign-up-new.php');
exit;

$error = '';
$success = '';
$step = $_GET['step'] ?? 'register';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        if ($action === 'register') {
            // Step 1: Initial registration
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            
            // Validation
            $errors = [];
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email address is required';
            }
            
            if (!str_ends_with(strtolower($email), '@gmail.com')) {
                $errors[] = 'Only Gmail addresses are allowed';
            }
            
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            if (empty($phone) || !preg_match('/^(\+92|0)?[0-9]{10}$/', str_replace([' ', '-', '(', ')'], '', $phone))) {
                $errors[] = 'Valid Pakistani phone number is required';
            }
            
            if (empty($firstName) || empty($lastName)) {
                $errors[] = 'First and last name are required';
            }
            
            // Enhanced name validation
            if (!preg_match('/^[a-zA-Z\s]{2,}$/', $firstName)) {
                $errors[] = 'First name must contain only letters and be at least 2 characters';
            }
            
            if (!preg_match('/^[a-zA-Z\s]{2,}$/', $lastName)) {
                $errors[] = 'Last name must contain only letters and be at least 2 characters';
            }
            
            // Enhanced password validation
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
                $errors[] = 'Password must contain at least 8 characters with uppercase, lowercase, number and special character';
            }
            
            // Check terms acceptance
            if (!isset($_POST['terms_accepted']) || $_POST['terms_accepted'] !== '1') {
                $errors[] = 'You must read and accept the Terms and Conditions';
            }
            
            if (!isset($_POST['privacy_accepted']) || $_POST['privacy_accepted'] !== '1') {
                $errors[] = 'You must read and accept the Privacy Policy';
            }
            
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
            } else {
                // Check if email already exists
                $existingUser = $db->fetchOne('SELECT id FROM tts_users WHERE email = ?', [$email]);
                if ($existingUser) {
                    $error = 'Email address is already registered';
                } else {
                    // Create user record
                    $userData = [
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'role' => 'visitor',
                        'status' => 'PENDING_VERIFICATION',
                        'email_verified' => false,
                        'phone_verified' => false,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $userId = $db->insert('tts_users', $userData);
                    
                    // For now, directly activate the user (skip OTP verification)
                    $db->update('tts_users', [
                        'status' => 'ACTIVE',
                        'email_verified' => true
                    ], 'id = ?', [$userId]);
                    
                    // Set success message and redirect
                    $_SESSION['registration_success'] = true;
                    
                    log_message('info', 'User registration initiated', [
                        'user_id' => $userId,
                        'email' => $email,
                        'phone' => $phone
                    ]);
                    
                    header('Location: sign-up.php?step=verify');
                    exit;
                }
            }
            
        } elseif ($action === 'verify_otp') {
            // Step 2: OTP Verification
            if (!isset($_SESSION['registration_user_id'])) {
                $error = 'Registration session expired. Please start over.';
            } else {
                $emailOTP = trim($_POST['email_otp'] ?? '');
                $smsOTP = trim($_POST['sms_otp'] ?? '');
                $userId = $_SESSION['registration_user_id'];
                
                if (empty($emailOTP) || empty($smsOTP)) {
                    $error = 'Both OTP codes are required';
                } else {
                    // Verify OTP codes
                    $emailValid = $db->exists(
                        'tts_otp_codes',
                        'user_id = ? AND code = ? AND type = ? AND purpose = ? AND expires_at > NOW() AND verified_at IS NULL',
                        [$userId, $emailOTP, 'email', 'registration']
                    );
                    
                    $smsValid = $db->exists(
                        'tts_otp_codes',
                        'user_id = ? AND code = ? AND type = ? AND purpose = ? AND expires_at > NOW() AND verified_at IS NULL',
                        [$userId, $smsOTP, 'sms', 'registration']
                    );
                    
                    if (!$emailValid || !$smsValid) {
                        $error = 'Invalid or expired OTP codes';
                        
                        // Increment attempts
                        $db->query(
                            'UPDATE tts_otp_codes SET attempts = attempts + 1 WHERE user_id = ? AND purpose = ?',
                            [$userId, 'registration']
                        );
                    } else {
                        // Mark OTPs as verified
                        $db->update(
                            'tts_otp_codes',
                            ['verified_at' => date('Y-m-d H:i:s')],
                            'user_id = ? AND purpose = ?',
                            [$userId, 'registration']
                        );
                        
                        // Update user meta
                        $db->update(
                            'tts_users_meta',
                            [
                                'gmail_verified' => true,
                                'mobile_verified' => true
                            ],
                            'user_id = ?',
                            [$userId]
                        );
                        
                        // Set user session
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['role'] = 'visitor';
                        $_SESSION['email'] = $_SESSION['registration_email'];
                        
                        // Clean up registration session data
                        unset($_SESSION['registration_user_id']);
                        unset($_SESSION['registration_email']);
                        unset($_SESSION['registration_phone']);
                        unset($_SESSION['registration_name']);
                        unset($_SESSION['demo_email_otp']);
                        unset($_SESSION['demo_sms_otp']);
                        
                        log_message('info', 'User registration completed', [
                            'user_id' => $userId,
                            'email' => $_SESSION['email']
                        ]);
                        
                        header('Location: ../dashboard/visitor/');
                        exit;
                    }
                }
            }
            
        } elseif ($action === 'resend_otp') {
            // Resend OTP codes
            if (!isset($_SESSION['registration_user_id'])) {
                $error = 'Registration session expired. Please start over.';
            } else {
                $userId = $_SESSION['registration_user_id'];
                $email = $_SESSION['registration_email'];
                $phone = $_SESSION['registration_phone'];
                
                // Generate new OTP codes
                $emailOTP = sprintf('%06d', rand(100000, 999999));
                $smsOTP = sprintf('%06d', rand(100000, 999999));
                
                // Invalidate old OTPs
                $db->query(
                    'UPDATE tts_otp_codes SET expires_at = NOW() WHERE user_id = ? AND purpose = ? AND verified_at IS NULL',
                    [$userId, 'registration']
                );
                
                // Insert new OTPs
                $emailOTPData = [
                    'user_id' => $userId,
                    'email' => $email,
                    'code' => $emailOTP,
                    'type' => 'email',
                    'purpose' => 'registration',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ];
                
                $smsOTPData = [
                    'user_id' => $userId,
                    'phone' => $phone,
                    'code' => $smsOTP,
                    'type' => 'sms',
                    'purpose' => 'registration',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ];
                
                $db->insert('tts_otp_codes', $emailOTPData);
                $db->insert('tts_otp_codes', $smsOTPData);
                
                // Update demo OTPs
                $_SESSION['demo_email_otp'] = $emailOTP;
                $_SESSION['demo_sms_otp'] = $smsOTP;
                
                $success = 'New OTP codes have been sent';
            }
        }
        
    } catch (Exception $e) {
        log_message('error', 'Registration error: ' . $e->getMessage());
        $error = 'Registration failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Sign Up</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #6c757d;
            --success-color: #00b74a;
            --danger-color: #f93154;
            --warning-color: #fbbd08;
            --info-color: #39c0ed;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        
        .auth-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        .form-outline {
            margin-bottom: 1.5rem;
        }
        
        .btn-auth {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(18, 102, 241, 0.3);
        }
        
        .otp-input {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5rem;
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
            background: #e9ecef;
            color: #6c757d;
            font-weight: 600;
        }
        
        .step.active {
            background: var(--primary-color);
            color: white;
        }
        
        .step.completed {
            background: var(--success-color);
            color: white;
        }
        
        .step-line {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin-top: 19px;
        }
        
        .step-line.completed {
            background: var(--success-color);
        }
        
        .demo-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .countdown {
            font-weight: 600;
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="card auth-card">
            <div class="card-body p-4">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="mb-1">Join TTS Network</h3>
                    <p class="text-muted mb-0">Create your professional account</p>
                </div>
                
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?php echo $step === 'register' ? 'active' : ($step === 'verify' ? 'completed' : ''); ?>">1</div>
                    <div class="step-line <?php echo $step === 'verify' ? 'completed' : ''; ?>"></div>
                    <div class="step <?php echo $step === 'verify' ? 'active' : ''; ?>">2</div>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($step === 'register'): ?>
                <!-- Registration Form -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                <label class="form-label" for="first_name">First Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-outline">
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                <label class="form-label" for="last_name">Last Name</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-outline">
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <label class="form-label" for="email">Gmail Address</label>
                        <div class="form-text">Only @gmail.com addresses are accepted</div>
                    </div>
                    
                    <div class="form-outline">
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                        <label class="form-label" for="phone">Phone Number</label>
                        <div class="form-text">Pakistani mobile number (e.g., 03001234567)</div>
                    </div>
                    
                    <div class="form-outline position-relative">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <label class="form-label" for="password">Password</label>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" 
                                onclick="togglePassword('password')" style="z-index: 10;">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                        <div class="form-text">
                            Must contain: 8+ characters, uppercase, lowercase, number, special character
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="generatePassword()">
                                Generate Strong Password
                            </button>
                        </div>
                        <div class="password-strength mt-1">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar" id="password-strength-bar" style="width: 0%"></div>
                            </div>
                            <small id="password-strength-text" class="text-muted">Password strength</small>
                        </div>
                    </div>
                    
                    <div class="form-outline position-relative">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" 
                                onclick="togglePassword('confirm_password')" style="z-index: 10;">
                            <i class="fas fa-eye" id="confirm_password-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Legal Documents Compliance -->
                    <div class="legal-compliance mb-3">
                        <div class="card">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-3">Legal Requirements</h6>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="terms_read" name="terms_read" required>
                                    <label class="form-check-label" for="terms_read">
                                        I have read and understood the 
                                        <a href="../../../legal/terms.php" target="_blank" onclick="trackLegalView('terms')">
                                            Terms & Conditions
                                        </a>
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="privacy_read" name="privacy_read" required>
                                    <label class="form-check-label" for="privacy_read">
                                        I have read and understood the 
                                        <a href="../../../legal/privacy.php" target="_blank" onclick="trackLegalView('privacy')">
                                            Privacy Policy
                                        </a>
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required>
                                    <label class="form-check-label" for="terms_accepted">
                                        I accept and agree to be bound by the Terms & Conditions
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="privacy_accepted" name="privacy_accepted" value="1" required>
                                    <label class="form-check-label" for="privacy_accepted">
                                        I consent to the collection and processing of my data as described in the Privacy Policy
                                        <span class="text-danger">*</span>
                                    </label>
                                </div>
                                
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        You must read and accept all legal documents to proceed
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- OTP Verification Notice -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading"><i class="fas fa-shield-alt me-2"></i>Verification Required</h6>
                        <p class="mb-0">After registration, you'll receive OTP codes via email and SMS for account verification. Both codes are required to complete registration.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="fas fa-user-plus me-2"></i>
                        Create Account
                    </button>
                </form>
                
                <?php elseif ($step === 'verify'): ?>
                <!-- OTP Verification Form -->
                <div class="text-center mb-4">
                    <h5>Verify Your Account</h5>
                    <p class="text-muted">
                        We've sent verification codes to:<br>
                        <strong><?php echo htmlspecialchars($_SESSION['registration_email'] ?? ''); ?></strong><br>
                        <strong><?php echo htmlspecialchars($_SESSION['registration_phone'] ?? ''); ?></strong>
                    </p>
                </div>
                
                <?php if (isset($_SESSION['demo_email_otp']) && isset($_SESSION['demo_sms_otp'])): ?>
                <div class="demo-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Demo Mode</h6>
                    <p class="mb-2">For demonstration purposes, here are your OTP codes:</p>
                    <p class="mb-1"><strong>Email OTP:</strong> <code><?php echo $_SESSION['demo_email_otp']; ?></code></p>
                    <p class="mb-0"><strong>SMS OTP:</strong> <code><?php echo $_SESSION['demo_sms_otp']; ?></code></p>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify_otp">
                    
                    <div class="form-outline">
                        <input type="text" id="email_otp" name="email_otp" class="form-control otp-input" 
                               maxlength="6" pattern="[0-9]{6}" required>
                        <label class="form-label" for="email_otp">Email OTP Code</label>
                    </div>
                    
                    <div class="form-outline">
                        <input type="text" id="sms_otp" name="sms_otp" class="form-control otp-input" 
                               maxlength="6" pattern="[0-9]{6}" required>
                        <label class="form-label" for="sms_otp">SMS OTP Code</label>
                    </div>
                    
                    <div class="text-center mb-3">
                        <small class="text-muted">
                            Codes expire in <span id="countdown" class="countdown">10:00</span>
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="fas fa-check-circle me-2"></i>
                        Verify & Complete Registration
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="resend_otp">
                        <button type="submit" class="btn btn-link p-0">
                            <i class="fas fa-redo me-1"></i>Resend OTP Codes
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <p class="mb-0">
                        Already have an account? 
                        <a href="sign-in.php" class="text-decoration-none">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="../../../" class="text-white text-decoration-none opacity-75">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Main Website
            </a>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // OTP input formatting
        document.querySelectorAll('.otp-input').forEach(input => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Auto-format with spaces
                if (this.value.length === 6) {
                    this.value = this.value.replace(/(\d{3})(\d{3})/, '$1 $2');
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = paste.replace(/[^0-9]/g, '').substring(0, 6);
                this.value = numbers;
                if (numbers.length === 6) {
                    this.value = numbers.replace(/(\d{3})(\d{3})/, '$1 $2');
                }
            });
        });
        
        // Countdown timer for OTP expiry
        <?php if ($step === 'verify'): ?>
        let timeLeft = 600; // 10 minutes in seconds
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                countdownElement.textContent = 'Expired';
                countdownElement.classList.add('text-danger');
                // Optionally disable form or show resend button
            } else {
                timeLeft--;
            }
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        <?php endif; ?>
        
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const checks = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                number: /\d/.test(password),
                special: /[@$!%*?&]/.test(password)
            };
            
            strength = Object.values(checks).filter(Boolean).length;
            
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            const levels = [
                { width: 0, color: 'bg-danger', text: 'Very Weak' },
                { width: 20, color: 'bg-danger', text: 'Weak' },
                { width: 40, color: 'bg-warning', text: 'Fair' },
                { width: 60, color: 'bg-info', text: 'Good' },
                { width: 80, color: 'bg-success', text: 'Strong' },
                { width: 100, color: 'bg-success', text: 'Very Strong' }
            ];
            
            const level = levels[strength];
            strengthBar.style.width = level.width + '%';
            strengthBar.className = 'progress-bar ' + level.color;
            strengthText.textContent = level.text;
            
            return strength >= 4; // Require at least 4/5 criteria
        }
        
        // Password generator
        function generatePassword() {
            const length = 12;
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@$!%*?&';
            let password = '';
            
            // Ensure at least one of each required type
            password += 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)];
            password += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)];
            password += '0123456789'[Math.floor(Math.random() * 10)];
            password += '@$!%*?&'[Math.floor(Math.random() * 7)];
            
            // Fill the rest randomly
            for (let i = 4; i < length; i++) {
                password += charset[Math.floor(Math.random() * charset.length)];
            }
            
            // Shuffle the password
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            document.getElementById('password').value = password;
            document.getElementById('confirm_password').value = password;
            checkPasswordStrength(password);
        }
        
        // Legal document tracking
        let legalViews = { terms: false, privacy: false };
        
        function trackLegalView(type) {
            legalViews[type] = true;
            setTimeout(() => {
                const readCheckbox = document.getElementById(type + '_read');
                if (readCheckbox && !readCheckbox.checked) {
                    readCheckbox.checked = true;
                }
            }, 3000); // Auto-check after 3 seconds of viewing
        }
        
        // Password strength monitoring
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const action = document.querySelector('input[name="action"]').value;
            
            if (action === 'register') {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const firstName = document.getElementById('first_name').value;
                const lastName = document.getElementById('last_name').value;
                
                // Email validation
                if (!email.toLowerCase().endsWith('@gmail.com')) {
                    e.preventDefault();
                    alert('Only Gmail addresses are allowed.');
                    return false;
                }
                
                // Name validation
                if (!/^[a-zA-Z\s]{2,}$/.test(firstName) || !/^[a-zA-Z\s]{2,}$/.test(lastName)) {
                    e.preventDefault();
                    alert('Names must contain only letters and be at least 2 characters long.');
                    return false;
                }
                
                // Password validation
                if (!checkPasswordStrength(password)) {
                    e.preventDefault();
                    alert('Password must meet all security requirements.');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return false;
                }
                
                // Legal compliance validation
                const requiredCheckboxes = ['terms_read', 'privacy_read', 'terms_accepted', 'privacy_accepted'];
                for (let id of requiredCheckboxes) {
                    if (!document.getElementById(id).checked) {
                        e.preventDefault();
                        alert('You must read and accept all legal documents to proceed.');
                        return false;
                    }
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long.');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return false;
                }
            } else if (action === 'verify_otp') {
                const emailOTP = document.getElementById('email_otp').value.replace(/\s/g, '');
                const smsOTP = document.getElementById('sms_otp').value.replace(/\s/g, '');
                
                if (emailOTP.length !== 6 || smsOTP.length !== 6) {
                    e.preventDefault();
                    alert('Please enter both 6-digit OTP codes.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
