<?php
/**
 * TTS PMS - User Registration with Email Verification Only
 * Gmail-only registration with email verification using PHPMailer
 */

// Load configuration
require_once '../../../config/init.php';
require_once '../../../config/email_config.php';

// Start session
session_start();

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
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? ''); // Optional now
            
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
            
            if (empty($firstName) || empty($lastName)) {
                $errors[] = 'First name and last name are required';
            }
            
            // Check if email already exists
            $existingUser = $db->fetchOne('SELECT id FROM tts_users WHERE email = ?', [$email]);
            if ($existingUser) {
                $errors[] = 'Email address is already registered';
            }
            
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
            } else {
                // Generate verification token
                $verificationToken = generateVerificationToken();
                $tokenExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user with pending verification status
                $userData = [
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone ?: null,
                    'role' => 'visitor',
                    'status' => 'PENDING_VERIFICATION',
                    'email_verified' => false,
                    'phone_verified' => false,
                    'verification_token' => $verificationToken,
                    'token_expiry' => $tokenExpiry,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $userId = $db->insert('tts_users', $userData);
                
                if ($userId) {
                    // Send verification email
                    if (sendVerificationEmail($email, $verificationToken, $firstName)) {
                        $success = 'Registration successful! Please check your email to verify your account.';
                        $_SESSION['registration_email'] = $email;
                        header('Location: sign-up-new.php?step=verify');
                        exit;
                    } else {
                        $error = 'Registration successful but failed to send verification email. Please contact support.';
                    }
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        $error = 'An error occurred during registration. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - TTS WorkHub</title>
    
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
        
        .signup-container {
            width: 100%;
            max-width: 500px;
        }
        
        .signup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .signup-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .signup-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .signup-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating > .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-signup {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1rem;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .requirement i {
            margin-right: 0.5rem;
            width: 16px;
        }
        
        .requirement.valid {
            color: var(--success-color);
        }
        
        .requirement.invalid {
            color: var(--danger-color);
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .verification-step {
            text-align: center;
            padding: 2rem;
        }
        
        .verification-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--success-color), #20c997);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: white;
        }
        
        .resend-email {
            background: none;
            border: none;
            color: var(--primary-color);
            text-decoration: underline;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .resend-email:hover {
            color: var(--secondary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .signup-header {
                padding: 1.5rem;
            }
            
            .signup-body {
                padding: 1.5rem;
            }
            
            .signup-logo {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .verification-icon {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .signup-header {
                padding: 1rem;
            }
            
            .signup-body {
                padding: 1rem;
            }
            
            .form-floating {
                margin-bottom: 0.75rem;
            }
            
            .password-requirements {
                padding: 0.75rem;
            }
            
            .verification-step {
                padding: 1.5rem;
            }
        }
        
        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <?php if ($step === 'register'): ?>
            <!-- Registration Form -->
            <div class="signup-header">
                <div class="signup-logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="mb-1">Join TTS WorkHub</h1>
                <p class="mb-0">Precision Data, Global Talent</p>
            </div>
            
            <div class="signup-body">
                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" id="signupForm" novalidate>
                    <input type="hidden" name="action" value="register">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="firstName" name="first_name" 
                                       placeholder="First Name" required 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                <label for="firstName">First Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="lastName" name="last_name" 
                                       placeholder="Last Name" required 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                <label for="lastName">Last Name *</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@gmail.com" required 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <label for="email">Gmail Address *</label>
                        <div class="form-text">Only Gmail addresses are accepted</div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               placeholder="+92 300 1234567" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <label for="phone">Phone Number (Optional)</label>
                        <div class="form-text">Include country code (e.g., +92 for Pakistan)</div>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required>
                        <label for="password">Password *</label>
                    </div>
                    
                    <div class="form-floating">
                        <input type="password" class="form-control" id="confirmPassword" name="confirm_password" 
                               placeholder="Confirm Password" required>
                        <label for="confirmPassword">Confirm Password *</label>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <h6 class="mb-2">Password Requirements:</h6>
                        <div class="requirement" id="req-length">
                            <i class="fas fa-times"></i>
                            <span>At least 8 characters long</span>
                        </div>
                        <div class="requirement" id="req-match">
                            <i class="fas fa-times"></i>
                            <span>Passwords match</span>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                            <a href="#" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-signup" id="submitBtn">
                        <span class="btn-text">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Creating Account...
                        </span>
                    </button>
                </form>
                
                <div class="back-link">
                    <p class="mb-0">Already have an account? <a href="sign-in.php">Sign In</a></p>
                </div>
            </div>
            
            <?php elseif ($step === 'verify'): ?>
            <!-- Email Verification Step -->
            <div class="verification-step">
                <div class="verification-icon">
                    <i class="fas fa-envelope-open"></i>
                </div>
                
                <h2 class="mb-3">Check Your Email</h2>
                <p class="mb-4">
                    We've sent a verification link to<br>
                    <strong><?php echo htmlspecialchars($_SESSION['registration_email'] ?? 'your email'); ?></strong>
                </p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Next Steps:</strong>
                    <ol class="mb-0 mt-2 text-start">
                        <li>Check your Gmail inbox</li>
                        <li>Look for an email from TTS WorkHub</li>
                        <li>Click the verification link</li>
                        <li>Return here to sign in</li>
                    </ol>
                </div>
                
                <p class="text-muted">
                    Didn't receive the email? Check your spam folder or 
                    <button type="button" class="resend-email" onclick="resendVerification()">
                        click here to resend
                    </button>
                </p>
                
                <div class="mt-4">
                    <a href="sign-in.php" class="btn btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Go to Sign In
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and password requirements
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('signupForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn?.querySelector('.btn-text');
            const btnLoading = submitBtn?.querySelector('.btn-loading');
            
            // Email validation for Gmail only
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const email = this.value.toLowerCase();
                    if (email && !email.endsWith('@gmail.com')) {
                        this.setCustomValidity('Only Gmail addresses are allowed');
                        this.classList.add('is-invalid');
                    } else {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid');
                        if (email.includes('@gmail.com')) {
                            this.classList.add('is-valid');
                        }
                    }
                });
            }
            
            // Password requirements validation
            function validatePassword() {
                if (!passwordInput || !confirmPasswordInput) return;
                
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Length requirement
                const lengthReq = document.getElementById('req-length');
                if (password.length >= 8) {
                    lengthReq.classList.add('valid');
                    lengthReq.classList.remove('invalid');
                    lengthReq.querySelector('i').className = 'fas fa-check';
                } else {
                    lengthReq.classList.add('invalid');
                    lengthReq.classList.remove('valid');
                    lengthReq.querySelector('i').className = 'fas fa-times';
                }
                
                // Password match requirement
                const matchReq = document.getElementById('req-match');
                if (password && confirmPassword && password === confirmPassword) {
                    matchReq.classList.add('valid');
                    matchReq.classList.remove('invalid');
                    matchReq.querySelector('i').className = 'fas fa-check';
                    confirmPasswordInput.classList.remove('is-invalid');
                    confirmPasswordInput.classList.add('is-valid');
                } else if (confirmPassword) {
                    matchReq.classList.add('invalid');
                    matchReq.classList.remove('valid');
                    matchReq.querySelector('i').className = 'fas fa-times';
                    confirmPasswordInput.classList.add('is-invalid');
                    confirmPasswordInput.classList.remove('is-valid');
                }
            }
            
            if (passwordInput) {
                passwordInput.addEventListener('input', validatePassword);
            }
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', validatePassword);
            }
            
            // Form submission
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Show loading state
                    if (btnText && btnLoading) {
                        btnText.classList.add('d-none');
                        btnLoading.classList.remove('d-none');
                        submitBtn.disabled = true;
                        form.classList.add('loading');
                    }
                    
                    // Validate Gmail requirement
                    const email = emailInput.value.toLowerCase();
                    if (!email.endsWith('@gmail.com')) {
                        e.preventDefault();
                        alert('Only Gmail addresses are allowed for registration.');
                        resetSubmitButton();
                        return false;
                    }
                    
                    // Validate password requirements
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long.');
                        resetSubmitButton();
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match.');
                        resetSubmitButton();
                        return false;
                    }
                });
            }
            
            function resetSubmitButton() {
                if (btnText && btnLoading && submitBtn) {
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                    submitBtn.disabled = false;
                    form.classList.remove('loading');
                }
            }
        });
        
        // Resend verification email
        function resendVerification() {
            // You can implement this functionality to resend verification email
            alert('Verification email resent! Please check your inbox.');
        }
        
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[type="text"], input[type="email"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>
