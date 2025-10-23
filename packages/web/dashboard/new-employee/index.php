<?php
/**
 * TTS PMS - New Employee Dashboard
 * Onboarding and training interface
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has new_employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'new_employee') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("New employee dashboard error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Demo onboarding tasks
$onboardingTasks = [
    [
        'id' => 1,
        'title' => 'Complete Profile Setup',
        'description' => 'Fill in your personal and professional information',
        'status' => 'completed',
        'priority' => 'high',
        'due_date' => date('Y-m-d', strtotime('+1 day')),
        'completed_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
    [
        'id' => 2,
        'title' => 'Review Company Policies',
        'description' => 'Read and acknowledge company policies and procedures',
        'status' => 'in_progress',
        'priority' => 'high',
        'due_date' => date('Y-m-d', strtotime('+2 days')),
        'completed_date' => null
    ],
    [
        'id' => 3,
        'title' => 'Complete Security Training',
        'description' => 'Mandatory cybersecurity and data protection training',
        'status' => 'pending',
        'priority' => 'high',
        'due_date' => date('Y-m-d', strtotime('+3 days')),
        'completed_date' => null
    ],
    [
        'id' => 4,
        'title' => 'Setup Development Environment',
        'description' => 'Install required software and tools',
        'status' => 'pending',
        'priority' => 'medium',
        'due_date' => date('Y-m-d', strtotime('+5 days')),
        'completed_date' => null
    ],
    [
        'id' => 5,
        'title' => 'Meet Your Team',
        'description' => 'Schedule introductory meetings with team members',
        'status' => 'pending',
        'priority' => 'medium',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
        'completed_date' => null
    ]
];

// Demo training modules
$trainingModules = [
    [
        'id' => 1,
        'title' => 'Data Entry Best Practices',
        'description' => 'Learn efficient and accurate data entry techniques',
        'duration' => '45 minutes',
        'status' => 'completed',
        'progress' => 100,
        'completed_date' => date('Y-m-d', strtotime('-2 days'))
    ],
    [
        'id' => 2,
        'title' => 'Quality Assurance Standards',
        'description' => 'Understanding our quality control processes',
        'duration' => '30 minutes',
        'status' => 'in_progress',
        'progress' => 60,
        'completed_date' => null
    ],
    [
        'id' => 3,
        'title' => 'Client Communication Guidelines',
        'description' => 'Professional communication standards and protocols',
        'duration' => '25 minutes',
        'status' => 'not_started',
        'progress' => 0,
        'completed_date' => null
    ],
    [
        'id' => 4,
        'title' => 'Time Management & Productivity',
        'description' => 'Tools and techniques for effective time management',
        'duration' => '35 minutes',
        'status' => 'not_started',
        'progress' => 0,
        'completed_date' => null
    ]
];

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $taskId = (int)($_POST['task_id'] ?? 0);
    
    if ($taskId) {
        // In real implementation, update database
        $message = 'Task marked as completed successfully!';
        $messageType = 'success';
        
        // Update demo data
        foreach ($onboardingTasks as &$task) {
            if ($task['id'] == $taskId) {
                $task['status'] = 'completed';
                $task['completed_date'] = date('Y-m-d H:i:s');
                break;
            }
        }
    }
}

// Calculate progress
$completedTasks = count(array_filter($onboardingTasks, function($task) { return $task['status'] === 'completed'; }));
$totalTasks = count($onboardingTasks);
$onboardingProgress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$completedModules = count(array_filter($trainingModules, function($module) { return $module['status'] === 'completed'; }));
$totalModules = count($trainingModules);
$trainingProgress = $totalModules > 0 ? round(($completedModules / $totalModules) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Employee Dashboard - TTS PMS</title>
    
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
            background: linear-gradient(135deg, #00b74a, #28a745);
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
        
        .progress-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .task-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .task-item:hover {
            border-color: #00b74a;
            box-shadow: 0 2px 10px rgba(0, 183, 74, 0.1);
        }
        
        .task-completed {
            background-color: #f8f9fa;
            border-color: #00b74a;
        }
        
        .task-high {
            border-left: 4px solid #f93154;
        }
        
        .task-medium {
            border-left: 4px solid #fbbd08;
        }
        
        .task-low {
            border-left: 4px solid #39c0ed;
        }
        
        .training-module {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .training-module:hover {
            border-color: #1266f1;
            box-shadow: 0 2px 10px rgba(18, 102, 241, 0.1);
        }
        
        .module-completed {
            background-color: #f8f9fa;
            border-color: #00b74a;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .status-in_progress {
            background: linear-gradient(135deg, #39c0ed, #0dcaf0);
            color: white;
        }
        
        .status-pending, .status-not_started {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Welcome to TTS, <?php echo htmlspecialchars($_SESSION['name'] ?? 'New Employee'); ?>! 🎉</h2>
                    <p class="mb-0">Complete your onboarding journey to become a full team member</p>
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
                            <li><a class="dropdown-item" href="../../../../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Welcome Card -->
        <div class="card welcome-card">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-3">Your Onboarding Journey</h5>
                        <p class="mb-3">
                            Welcome to our team! We're excited to have you join us. Complete the tasks and training modules below 
                            to get fully onboarded and ready to contribute to our amazing projects.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tasks text-success me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Onboarding Tasks</h6>
                                        <small class="text-muted"><?php echo $completedTasks; ?> of <?php echo $totalTasks; ?> completed</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-graduation-cap text-primary me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Training Modules</h6>
                                        <small class="text-muted"><?php echo $completedModules; ?> of <?php echo $totalModules; ?> completed</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $onboardingProgress; ?>%"></div>
                        </div>
                        <h4 class="text-success mb-0"><?php echo $onboardingProgress; ?>%</h4>
                        <small class="text-muted">Overall Progress</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Onboarding Tasks -->
            <div class="col-lg-6">
                <div class="card progress-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>Onboarding Tasks
                            <span class="badge bg-success ms-2"><?php echo $completedTasks; ?>/<?php echo $totalTasks; ?></span>
                        </h5>
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="profile.php">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="timesheet.php">
                                    <i class="fas fa-clock me-2"></i>Timesheet
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="payslip.php">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>Payslip
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="leave.php">
                                    <i class="fas fa-calendar-times me-2"></i>Leave Management
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <?php foreach ($onboardingTasks as $task): ?>
                        <div class="task-item task-<?php echo $task['priority']; ?> <?php echo $task['status'] === 'completed' ? 'task-completed' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1">
                                    <?php if ($task['status'] === 'completed'): ?>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php elseif ($task['status'] === 'in_progress'): ?>
                                    <i class="fas fa-clock text-warning me-2"></i>
                                    <?php else: ?>
                                    <i class="fas fa-circle text-muted me-2"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </h6>
                                <span class="status-badge status-<?php echo $task['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </div>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($task['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Due: <?php echo date('M j, Y', strtotime($task['due_date'])); ?>
                                </small>
                                <?php if ($task['status'] !== 'completed'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="complete_task" value="1">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check me-1"></i>Mark Complete
                                    </button>
                                </form>
                                <?php else: ?>
                                <small class="text-success">
                                    <i class="fas fa-check me-1"></i>
                                    Completed <?php echo date('M j', strtotime($task['completed_date'])); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Training Modules -->
            <div class="col-lg-6">
                <div class="card progress-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>Training Modules
                            <span class="badge bg-primary ms-2"><?php echo $completedModules; ?>/<?php echo $totalModules; ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($trainingModules as $module): ?>
                        <div class="training-module <?php echo $module['status'] === 'completed' ? 'module-completed' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1">
                                    <?php if ($module['status'] === 'completed'): ?>
                                    <i class="fas fa-medal text-success me-2"></i>
                                    <?php elseif ($module['status'] === 'in_progress'): ?>
                                    <i class="fas fa-play-circle text-primary me-2"></i>
                                    <?php else: ?>
                                    <i class="fas fa-play text-muted me-2"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($module['title']); ?>
                                </h6>
                                <span class="status-badge status-<?php echo $module['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $module['status'])); ?>
                                </span>
                            </div>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($module['description']); ?></p>
                            
                            <?php if ($module['progress'] > 0): ?>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar" style="width: <?php echo $module['progress']; ?>%"></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Duration: <?php echo htmlspecialchars($module['duration']); ?>
                                </small>
                                <?php if ($module['status'] === 'completed'): ?>
                                <small class="text-success">
                                    <i class="fas fa-check me-1"></i>
                                    Completed <?php echo date('M j', strtotime($module['completed_date'])); ?>
                                </small>
                                <?php elseif ($module['status'] === 'in_progress'): ?>
                                <button class="btn btn-sm btn-primary">
                                    <i class="fas fa-play me-1"></i>Continue
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-play me-1"></i>Start Module
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
