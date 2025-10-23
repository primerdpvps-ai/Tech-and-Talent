<?php
/**
 * TTS PMS - User Sign In
 * Authentication with role-based dashboard redirection
 */

// Load configuration
require_once '../../../config/init.php';

// Start session
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $dashboards = [
        'visitor' => '../dashboard/visitor/',
        'candidate' => '../dashboard/candidate/',
        'new_employee' => '../dashboard/new-employee/',
        'employee' => '../dashboard/employee/',
        'manager' => '../dashboard/manager/',
        'ceo' => '../dashboard/ceo/',
        'admin' => '../../../admin/'
    ];
    
    $redirectUrl = $dashboards[$_SESSION['role']] ?? '../dashboard/visitor/';
    header("Location: {$redirectUrl}");
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
                'SELECT id, email, password_hash, first_name, last_name, role, status FROM tts_users WHERE email = ? AND status = ?',
                [$email, 'active']
            );
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Database user found and password verified
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                    'name' => 'Lisa CEO',
                    'verified' => true
                ]
            ];
            
            if (isset($demoUsers[$email]) && $demoUsers[$email]['password'] === $password) {
                $user = $demoUsers[$email];
                
                if (!$user['verified']) {
                    $error = 'Please verify your email and phone number before signing in.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['login_time'] = time();
                    
                    // Set remember me cookie if requested
                    if ($rememberMe) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                        // In production, store this token in database
                    }
                    
                    // Log successful login
                    log_message('info', 'User login successful', [
                        'user_id' => $user['user_id'],
                        'email' => $email,
                        'role' => $user['role'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    
                    // Redirect to appropriate dashboard
                    $dashboards = [
                        'visitor' => '../dashboard/visitor/',
                        'candidate' => '../dashboard/candidate/',
                        'new_employee' => '../dashboard/new-employee/',
                        'employee' => '../dashboard/employee/',
                        'manager' => '../dashboard/manager/',
                        'ceo' => '../dashboard/ceo/'
                    ];
                    
                    $redirectUrl = $dashboards[$user['role']] ?? '../dashboard/visitor/';
                    header("Location: {$redirectUrl}");
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
                
                // Log failed login attempt
                log_message('warning', 'Login attempt failed', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
        } catch (Exception $e) {
            log_message('error', 'Login system error: ' . $e->getMessage());
            $error = 'Login system error. Please try again.';
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
        }
        
        .auth-container {
            max-width: 450px;
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
        
        .demo-accounts {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .demo-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            margin: 0.25rem 0;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .demo-account:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="card auth-card">
            <div class="card-body p-4">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h3 class="mb-1">Welcome Back</h3>
                    <p class="text-muted mb-0">Sign in to your TTS account</p>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <!-- Demo Accounts -->
                <div class="demo-accounts">
                    <h6><i class="fas fa-info-circle me-2"></i>Demo Accounts</h6>
                    <p class="mb-2 small">Click any account to auto-fill credentials:</p>
                    
                    <div class="demo-account" onclick="fillCredentials('visitor@gmail.com', 'password123')">
                        <span><strong>Visitor</strong> - visitor@gmail.com</span>
                        <span class="badge bg-secondary role-badge">Visitor</span>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('candidate@gmail.com', 'password123')">
                        <span><strong>Candidate</strong> - candidate@gmail.com</span>
                        <span class="badge bg-info role-badge">Candidate</span>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('newemployee@gmail.com', 'password123')">
                        <span><strong>New Employee</strong> - newemployee@gmail.com</span>
                        <span class="badge bg-warning role-badge">New Employee</span>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('employee@gmail.com', 'password123')">
                        <span><strong>Employee</strong> - employee@gmail.com</span>
                        <span class="badge bg-success role-badge">Employee</span>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('manager@gmail.com', 'password123')">
                        <span><strong>Manager</strong> - manager@gmail.com</span>
                        <span class="badge bg-primary role-badge">Manager</span>
                    </div>
                    
                    <div class="demo-account" onclick="fillCredentials('ceo@gmail.com', 'password123')">
                        <span><strong>CEO</strong> - ceo@gmail.com</span>
                        <span class="badge bg-danger role-badge">CEO</span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <div class="form-outline">
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <label class="form-label" for="email">Email Address</label>
                    </div>
                    
                    <div class="form-outline">
                        <input type="password" id="password" name="password" class="form-control" required>
                        <label class="form-label" for="password">Password</label>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Remember me
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="forgot-password.php" class="text-decoration-none">
                                Forgot password?
                            </a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Sign In
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0">
                        Don't have an account? 
                        <a href="sign-up.php" class="text-decoration-none">Sign Up</a>
                    </p>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="mb-2"><strong>Quick Access:</strong></p>
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="../../../admin/login.php" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-shield-alt me-1"></i>Admin Panel
                        </a>
                        <a href="../../../" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-home me-1"></i>Main Website
                        </a>
                    </div>
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
        // Auto-focus on email field
        document.getElementById('email').focus();
        
        // Fill credentials function for demo accounts
        function fillCredentials(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            
            // Trigger MDB label animation
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            emailInput.focus();
            emailInput.blur();
            passwordInput.focus();
            passwordInput.blur();
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (!email.includes('@')) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });
        
        // Clear any error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-danger');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);
        
        // Show password toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
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
