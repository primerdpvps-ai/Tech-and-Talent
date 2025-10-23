<?php
/**
 * TTS PMS - Visitor Dashboard
 * Pre-requirements evaluation and application gateway
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has visitor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'visitor') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Visitor dashboard error: " . $e->getMessage());
    $db = null;
}

$error = '';
$success = '';

// Check if user has completed evaluation (use demo data if DB unavailable)
$evaluation = null;
if ($db) {
    try {
        $evaluation = $db->fetchOne(
            'SELECT * FROM tts_evaluations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
            [$_SESSION['user_id']]
        );
    } catch (Exception $e) {
        error_log("Evaluation fetch error: " . $e->getMessage());
        $evaluation = null;
    }
}

// Handle evaluation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation']) && $db) {
    try {
        $evaluationData = [
            'user_id' => $_SESSION['user_id'],
            'age' => (int)$_POST['age'],
            'device_type' => trim($_POST['device_type']),
            'ram_text' => trim($_POST['ram_text']),
            'processor_text' => trim($_POST['processor_text']),
            'has_stable_internet' => isset($_POST['has_stable_internet']),
            'provider' => trim($_POST['provider']),
            'link_speed' => trim($_POST['link_speed']),
            'users_sharing' => (int)$_POST['users_sharing'],
            'speedtest_url' => trim($_POST['speedtest_url'] ?? ''),
            'profession' => trim($_POST['profession']),
            'availability_windows' => json_encode($_POST['availability_windows'] ?? []),
            'confidentiality_ok' => isset($_POST['confidentiality_ok']),
            'typing_speed_ok' => isset($_POST['typing_speed_ok'])
        ];
        
        // Evaluate eligibility
        $reasons = [];
        $result = 'eligible';
        
        if ($evaluationData['age'] < 16) {
            $reasons[] = 'Minimum age requirement not met (16+ required)';
            $result = 'rejected';
        }
        
        if ($evaluationData['age'] < 18) {
            $reasons[] = 'Minor status requires guardian consent';
            if ($result !== 'rejected') $result = 'pending';
        }
        
        if (!$evaluationData['has_stable_internet']) {
            $reasons[] = 'Stable internet connection required';
            $result = 'rejected';
        }
        
        if ($evaluationData['users_sharing'] > 5) {
            $reasons[] = 'Too many users sharing internet connection';
            if ($result !== 'rejected') $result = 'pending';
        }
        
        if (!$evaluationData['confidentiality_ok']) {
            $reasons[] = 'Confidentiality agreement must be accepted';
            $result = 'rejected';
        }
        
        if (!$evaluationData['typing_speed_ok']) {
            $reasons[] = 'Minimum typing speed requirement not met';
            if ($result !== 'rejected') $result = 'pending';
        }
        
        // Check RAM requirement (basic parsing)
        $ramText = strtolower($evaluationData['ram_text']);
        if (strpos($ramText, '2gb') !== false || strpos($ramText, '2 gb') !== false) {
            $reasons[] = 'Minimum 4GB RAM required';
            if ($result !== 'rejected') $result = 'pending';
        }
        
        $evaluationData['result'] = $result;
        $evaluationData['reasons'] = json_encode($reasons);
        
        // Insert evaluation
        $db->insert('tts_evaluations', $evaluationData);
        
        // Update user role if eligible
        if ($result === 'eligible') {
            $_SESSION['role'] = 'candidate';
            $success = 'Evaluation completed successfully! You are now eligible to apply for positions.';
            
            // Redirect to candidate dashboard after 2 seconds
            header('refresh:2;url=../candidate/');
        } else {
            $success = 'Evaluation submitted. Status: ' . ucfirst($result);
        }
        
        // Refresh evaluation data
        $evaluation = $db->fetchOne(
            'SELECT * FROM tts_evaluations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
            [$_SESSION['user_id']]
        );
        
    } catch (Exception $e) {
        log_message('error', 'Evaluation submission failed: ' . $e->getMessage());
        $error = 'Failed to submit evaluation. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Visitor Dashboard</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .welcome-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .evaluation-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            background: #1266f1;
            color: white;
        }
        
        .step.completed {
            background: #00b74a;
            color: white;
        }
        
        .step-line {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            margin-top: 19px;
        }
        
        .step-line.completed {
            background: #00b74a;
        }
        
        .requirement-item {
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .requirement-item:hover {
            border-color: #1266f1;
            box-shadow: 0 2px 10px rgba(18, 102, 241, 0.1);
        }
        
        .evaluation-result {
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin-top: 2rem;
        }
        
        .result-eligible {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .result-pending {
            background: linear-gradient(135deg, #fbbd08, #ffc107);
            color: white;
        }
        
        .result-rejected {
            background: linear-gradient(135deg, #f93154, #dc3545);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="../../auth/logout.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
                <h2><i class="fas fa-eye me-2"></i>Visitor Dashboard</h2>
            </div>
            <div class="text-muted">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Visitor'); ?>!</h2>
                    <p class="mb-0">Complete your pre-requirements evaluation to join our professional network</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Progress Steps -->
        <div class="step-indicator">
            <div class="step completed">1</div>
            <div class="step-line completed"></div>
            <div class="step <?php echo $evaluation ? 'completed' : 'active'; ?>">2</div>
            <div class="step-line <?php echo $evaluation && $evaluation['result'] === 'eligible' ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $evaluation && $evaluation['result'] === 'eligible' ? 'active' : ''; ?>">3</div>
        </div>
        
        <div class="text-center mb-4">
            <h5>Step 2: Pre-Requirements Evaluation</h5>
            <p class="text-muted">Please complete the evaluation to determine your eligibility</p>
            <?php if (!$evaluation): ?>
            <a href="evaluation.php" class="btn btn-primary btn-lg">
                <i class="fas fa-clipboard-check me-2"></i>Start Evaluation
            </a>
            <?php endif; ?>
        </div>
        
        <?php if ($evaluation): ?>
        <!-- Evaluation Result -->
        <div class="evaluation-result result-<?php echo $evaluation['result']; ?>">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-2">
                        <i class="fas fa-<?php echo $evaluation['result'] === 'eligible' ? 'check-circle' : ($evaluation['result'] === 'pending' ? 'clock' : 'times-circle'); ?> me-2"></i>
                        Evaluation Status: <?php echo ucfirst($evaluation['result']); ?>
                    </h4>
                    <p class="mb-0">
                        <?php if ($evaluation['result'] === 'eligible'): ?>
                            Congratulations! You meet all requirements and can proceed to apply for positions.
                        <?php elseif ($evaluation['result'] === 'pending'): ?>
                            Your application is under review. We will contact you within 24-48 hours.
                        <?php else: ?>
                            Unfortunately, you do not meet the current requirements. Please review the feedback below.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <?php if ($evaluation['result'] === 'eligible'): ?>
                        <a href="../candidate/" class="btn btn-light btn-lg">
                            <i class="fas fa-arrow-right me-2"></i>
                            Proceed to Application
                        </a>
                    <?php elseif ($evaluation['result'] === 'pending'): ?>
                        <button class="btn btn-light btn-lg" disabled>
                            <i class="fas fa-clock me-2"></i>
                            Under Review
                        </button>
                    <?php else: ?>
                        <button class="btn btn-light btn-lg" onclick="location.reload()">
                            <i class="fas fa-redo me-2"></i>
                            Retake Evaluation
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($evaluation['reasons']): ?>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
            <div class="text-start">
                <h6>Evaluation Notes:</h6>
                <ul class="mb-0">
                    <?php
                    $reasons = json_decode($evaluation['reasons'], true);
                    if (is_array($reasons)) {
                        foreach ($reasons as $reason) {
                            echo '<li>' . htmlspecialchars($reason) . '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <!-- Welcome Card -->
        <div class="card welcome-card">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-3">Ready to Join Our Professional Network?</h5>
                        <p class="mb-3">
                            Before you can apply for positions, we need to evaluate your technical setup and availability. 
                            This helps us ensure you have the right environment for remote professional work.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-laptop text-primary me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Technical Assessment</h6>
                                        <small class="text-muted">Hardware & connectivity check</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-success me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Availability Review</h6>
                                        <small class="text-muted">Work schedule compatibility</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-warning me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Security Compliance</h6>
                                        <small class="text-muted">Confidentiality requirements</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-keyboard text-info me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Skills Verification</h6>
                                        <small class="text-muted">Basic competency check</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-clipboard-check fa-4x text-primary mb-3"></i>
                        <p class="text-muted mb-0">Takes about 5-10 minutes</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Evaluation Form -->
        <div class="card evaluation-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Pre-Requirements Evaluation Form</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="submit_evaluation" value="1">
                    
                    <!-- Personal Information -->
                    <div class="requirement-item">
                        <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" class="form-control" id="age" name="age" min="16" max="65" required>
                            </div>
                            <div class="col-md-6">
                                <label for="profession" class="form-label">Current Profession/Field</label>
                                <input type="text" class="form-control" id="profession" name="profession" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Technical Setup -->
                    <div class="requirement-item">
                        <h6><i class="fas fa-laptop me-2"></i>Technical Setup</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="device_type" class="form-label">Device Type</label>
                                <select class="form-select" id="device_type" name="device_type" required>
                                    <option value="">Select device type</option>
                                    <option value="Desktop PC">Desktop PC</option>
                                    <option value="Laptop">Laptop</option>
                                    <option value="All-in-One PC">All-in-One PC</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="ram_text" class="form-label">RAM Memory</label>
                                <select class="form-select" id="ram_text" name="ram_text" required>
                                    <option value="">Select RAM amount</option>
                                    <option value="2GB">2GB</option>
                                    <option value="4GB">4GB</option>
                                    <option value="8GB">8GB</option>
                                    <option value="16GB or more">16GB or more</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="processor_text" class="form-label">Processor Information</label>
                                <input type="text" class="form-control" id="processor_text" name="processor_text" 
                                       placeholder="e.g., Intel Core i5, AMD Ryzen 5" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Internet Connection -->
                    <div class="requirement-item">
                        <h6><i class="fas fa-wifi me-2"></i>Internet Connection</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="provider" class="form-label">Internet Service Provider</label>
                                <input type="text" class="form-control" id="provider" name="provider" 
                                       placeholder="e.g., PTCL, Nayatel, Jazz" required>
                            </div>
                            <div class="col-md-6">
                                <label for="link_speed" class="form-label">Connection Speed</label>
                                <select class="form-select" id="link_speed" name="link_speed" required>
                                    <option value="">Select speed</option>
                                    <option value="1-5 Mbps">1-5 Mbps</option>
                                    <option value="5-10 Mbps">5-10 Mbps</option>
                                    <option value="10-20 Mbps">10-20 Mbps</option>
                                    <option value="20+ Mbps">20+ Mbps</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="users_sharing" class="form-label">Users Sharing Connection</label>
                                <select class="form-select" id="users_sharing" name="users_sharing" required>
                                    <option value="">Select number</option>
                                    <option value="1">Just me</option>
                                    <option value="2">2 users</option>
                                    <option value="3">3 users</option>
                                    <option value="4">4 users</option>
                                    <option value="5">5 users</option>
                                    <option value="6">6+ users</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="speedtest_url" class="form-label">Speed Test Result (Optional)</label>
                                <input type="url" class="form-control" id="speedtest_url" name="speedtest_url" 
                                       placeholder="Paste speed test URL">
                            </div>
                        </div>
                        
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="has_stable_internet" name="has_stable_internet" required>
                            <label class="form-check-label" for="has_stable_internet">
                                I have a stable internet connection with minimal interruptions
                            </label>
                        </div>
                    </div>
                    
                    <!-- Availability -->
                    <div class="requirement-item">
                        <h6><i class="fas fa-clock me-2"></i>Availability</h6>
                        <p class="text-muted mb-3">Our operational hours are 11:00 AM - 2:00 AM PKT. Please select your available time slots:</p>
                        
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="morning" name="availability_windows[]" value="morning">
                                    <label class="form-check-label" for="morning">
                                        Morning (11:00 AM - 3:00 PM)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="afternoon" name="availability_windows[]" value="afternoon">
                                    <label class="form-check-label" for="afternoon">
                                        Afternoon (3:00 PM - 7:00 PM)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="evening" name="availability_windows[]" value="evening">
                                    <label class="form-check-label" for="evening">
                                        Evening (7:00 PM - 11:00 PM)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="night" name="availability_windows[]" value="night">
                                    <label class="form-check-label" for="night">
                                        Night (11:00 PM - 2:00 AM)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="late_night" name="availability_windows[]" value="late_night">
                                    <label class="form-check-label" for="late_night">
                                        Late Night (2:00 AM - 6:00 AM)*
                                    </label>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">*Available for employees with 10+ days tenure</small>
                    </div>
                    
                    <!-- Agreements -->
                    <div class="requirement-item">
                        <h6><i class="fas fa-shield-alt me-2"></i>Requirements & Agreements</h6>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confidentiality_ok" name="confidentiality_ok" required>
                            <label class="form-check-label" for="confidentiality_ok">
                                I understand and agree to maintain strict confidentiality of all client information and work materials
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="typing_speed_ok" name="typing_speed_ok" required>
                            <label class="form-check-label" for="typing_speed_ok">
                                I can type at least 30 words per minute with good accuracy
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>
                            Submit Evaluation
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const availabilityChecked = document.querySelectorAll('input[name="availability_windows[]"]:checked').length;
            
            if (availabilityChecked === 0) {
                e.preventDefault();
                alert('Please select at least one availability window.');
                return false;
            }
        });
        
        // Auto-redirect for eligible users
        <?php if ($evaluation && $evaluation['result'] === 'eligible'): ?>
        setTimeout(function() {
            if (confirm('You are eligible to proceed! Click OK to go to the application page.')) {
                window.location.href = '../candidate/';
            }
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
