<?php
/**
 * TTS PMS - New Employee Profile
 * Profile management for new employees
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has new_employee role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'new_employee') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'My Profile';
$currentPage = 'profile';

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        $updateData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('tts_users', $updateData, 'id = ?', [$_SESSION['user_id']]);
        
        // Update session data
        $_SESSION['name'] = $updateData['first_name'] . ' ' . $updateData['last_name'];
        
        $success = 'Profile updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update profile: ' . $e->getMessage();
    }
}

// Load current user data and onboarding tasks
try {
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM tts_users WHERE id = ?", [$_SESSION['user_id']]);
    
    // Get onboarding tasks
    $onboardingTasks = $db->fetchAll("
        SELECT * FROM tts_onboarding_tasks 
        WHERE user_id = ? 
        ORDER BY priority DESC, due_date ASC
    ", [$_SESSION['user_id']]);
    
    // Get training progress
    $trainingProgress = $db->fetchAll("
        SELECT utp.*, tm.title, tm.description, tm.duration, tm.is_mandatory
        FROM tts_user_training_progress utp
        JOIN tts_training_modules tm ON utp.training_module_id = tm.id
        WHERE utp.user_id = ?
        ORDER BY tm.is_mandatory DESC, utp.created_at ASC
    ", [$_SESSION['user_id']]);
    
} catch (Exception $e) {
    $user = [];
    $onboardingTasks = [];
    $trainingProgress = [];
}

// Calculate completion percentages
$totalTasks = count($onboardingTasks);
$completedTasks = count(array_filter($onboardingTasks, fn($task) => $task['status'] === 'completed'));
$taskProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$totalTraining = count($trainingProgress);
$completedTraining = count(array_filter($trainingProgress, fn($training) => $training['status'] === 'completed'));
$trainingProgressPercent = $totalTraining > 0 ? round(($completedTraining / $totalTraining) * 100) : 0;
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
        
        .profile-header {
            background: linear-gradient(135deg, #fd7e14 0%, #e55353 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            font-size: 3rem;
        }
        
        .progress-card {
            background: linear-gradient(135deg, #fd7e14 0%, #e55353 100%);
            color: white;
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
                    <a class="nav-link active" href="profile.php">
                        <i class="fas fa-user me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="settings.php">
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
            <h2><i class="fas fa-user me-2"></i>My Profile</h2>
            <div class="text-muted">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="alert alert-info mb-4">
            <i class="fas fa-star me-2"></i>
            <strong>Welcome to TTS!</strong> Complete your onboarding tasks and training modules to get started.
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

        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-light text-dark mt-2">
                            New Employee
                        </span>
                    </div>
                    
                    <div class="card-body p-4">
                        <h5 class="mb-4">Personal Information</h5>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-outline mb-4">
                                        <input type="text" id="first_name" name="first_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        <label class="form-label" for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-outline mb-4">
                                        <input type="text" id="last_name" name="last_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        <label class="form-label" for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-outline mb-4">
                                <input type="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <label class="form-label" for="email">Email Address</label>
                                <div class="form-text">Email cannot be changed. Contact admin if needed.</div>
                            </div>
                            
                            <div class="form-outline mb-4">
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                <label class="form-label" for="phone">Phone Number</label>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Onboarding Progress -->
            <div class="col-md-4">
                <!-- Progress Overview -->
                <div class="card progress-card mb-3">
                    <div class="card-body text-center">
                        <h5 class="card-title">Onboarding Progress</h5>
                        <div class="row">
                            <div class="col-6">
                                <div class="border-end border-light">
                                    <h3><?php echo $taskProgress; ?>%</h3>
                                    <small>Tasks</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h3><?php echo $trainingProgressPercent; ?>%</h3>
                                <small>Training</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onboarding Tasks -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-tasks me-2"></i>Onboarding Tasks</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($onboardingTasks)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">All tasks completed!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($onboardingTasks, 0, 5) as $task): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <small class="text-muted">Due: <?php echo date('M j', strtotime($task['due_date'])); ?></small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo match($task['status']) {
                                            'pending' => 'warning',
                                            'in_progress' => 'info',
                                            'completed' => 'success',
                                            default => 'secondary'
                                        };
                                    ?> ms-2">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($onboardingTasks) > 5): ?>
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-warning btn-sm">
                                    View All Tasks
                                </a>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Training Progress -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-graduation-cap me-2"></i>Training Modules</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($trainingProgress)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-book fa-2x mb-2"></i>
                                <p class="mb-0">No training assigned yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($trainingProgress, 0, 3) as $training): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($training['title']); ?>
                                            <?php if ($training['is_mandatory']): ?>
                                                <span class="badge bg-danger ms-1">Required</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($training['duration']); ?></small>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $training['progress_percentage']; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $training['progress_percentage']; ?>% complete</small>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-warning btn-sm">
                                    View All Training
                                </a>
                            </div>
                        <?php endif; ?>
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
