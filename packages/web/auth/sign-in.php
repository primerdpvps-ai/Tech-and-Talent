<?php
/**
 * TTS PMS - Sign In (Fixed Version)
 * User authentication interface
 */

// Load configuration
require_once '../../../config/init.php';

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Redirect to appropriate dashboard based on role
    $dashboards = [
        'visitor' => '../dashboard/visitor/',
        'candidate' => '../dashboard/candidate/',
        'new_employee' => '../dashboard/new-employee/',
        'employee' => '../dashboard/employee/',
        'manager' => '../dashboard/manager/',
        'ceo' => '../dashboard/ceo/'
    ];
    
    $role = $_SESSION['role'] ?? 'visitor';
    $redirectUrl = $dashboards[$role] ?? '../dashboard/visitor/';
    header('Location: ' . $redirectUrl);
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            // Try database authentication first
            $db = Database::getInstance();
            $user = $db->fetchOne(
                'SELECT id, email, password_hash, first_name, last_name, role, status, email_verified FROM tts_users WHERE email = ?',
                [$email]
            );
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if email is verified
                if (!$user['email_verified'] || $user['status'] === 'PENDING_VERIFICATION') {
                    $error = 'Please verify your email address before signing in. Check your inbox for the verification link.';
                    $authenticatedRole = null;
                } elseif ($user['status'] !== 'ACTIVE') {
                    $error = 'Your account is not active. Please contact support.';
                    $authenticatedRole = null;
                } else {
                    // Database user found and password verified
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Update last login
                    $db->update('tts_users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                    
                    $authenticatedRole = $user['role'];
                }
            } else {
                $error = 'Invalid email or password.';
                $authenticatedRole = null;
            }
            
            // Handle successful authentication
            if (isset($authenticatedRole)) {
                // Set remember me cookie if requested
                if ($remember) {
                    setcookie('remember_me', $email, time() + (86400 * 30), '/', '', true, true); // 30 days, secure, httponly
                }
                
                // Log successful login
                if (function_exists('log_message')) {
                    log_message('info', 'User logged in successfully', [
                        'email' => $email,
                        'role' => $authenticatedRole,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                }
                
                // Redirect based on role
                $redirects = [
                    'visitor' => '../dashboard/visitor/',
                    'candidate' => '../dashboard/candidate/',
                    'new_employee' => '../dashboard/new-employee/',
                    'employee' => '../dashboard/employee/',
                    'manager' => '../dashboard/manager/',
                    'ceo' => '../dashboard/ceo/'
                ];
                
                $redirectUrl = $redirects[$authenticatedRole] ?? '../dashboard/visitor/';
                
                // Force session write and close
                session_write_close();
                session_start();
                
                header('Location: ' . $redirectUrl);
                exit;
            }
            
        } catch (Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            $error = 'Authentication system temporarily unavailable. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Sign In</title>
    
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
        
        .password-toggle:hover {
            color: #1266f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card auth-card">
                    <div class="auth-header">
                        <i class="fas fa-user-circle fa-3x mb-3"></i>
                        <h3 class="mb-0">Welcome Back</h3>
                        <p class="mb-0 opacity-75">Sign in to your TTS account</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Alert -->
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Success Alert -->
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        
                        <!-- Login Form -->
                        <form method="POST">
                            <div class="form-outline">
                                <input type="email" id="email" name="email" class="form-control" required>
                                <label class="form-label" for="email">Email Address</label>
                            </div>
                            
                            <div class="form-outline position-relative">
                                <input type="password" id="password" name="password" class="form-control" required>
                                <label class="form-label" for="password">Password</label>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                </button>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                        <label class="form-check-label" for="remember_me">
                                            Remember me
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">
                                Don't have an account? 
                                <a href="sign-up.php" class="text-decoration-none">Sign Up</a>
                            </p>
                            <p class="mb-0 mt-2">
                                <strong>Quick Access:</strong>
                            </p>
                            <div class="d-flex justify-content-center gap-3 mt-2">
                                <a href="../../../admin/login.php" class="text-decoration-none small">
                                    <i class="fas fa-shield-alt me-1"></i>Admin Panel
                                </a>
                                <a href="../../../index.php" class="text-decoration-none small">
                                    <i class="fas fa-home me-1"></i>Main Website
                                </a>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="../../../index.php" class="text-muted text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Main Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Password toggle function
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
