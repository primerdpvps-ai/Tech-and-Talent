<?php
/**
 * TTS PMS - Employee Dashboard
 * Daily work management and productivity tracking
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has employee role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Employee dashboard error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Demo timesheet data
$todayTimesheet = [
    'date' => date('Y-m-d'),
    'clock_in' => '09:15:00',
    'clock_out' => null,
    'break_start' => null,
    'break_end' => null,
    'total_hours' => 0,
    'status' => 'active'
];

// Demo tasks for today
$todayTasks = [
    [
        'id' => 1,
        'title' => 'Data Entry - Client ABC Database',
        'description' => 'Enter customer information from scanned forms',
        'priority' => 'high',
        'status' => 'in_progress',
        'assigned_time' => '09:30:00',
        'estimated_hours' => 3,
        'completed_percentage' => 65
    ],
    [
        'id' => 2,
        'title' => 'Quality Check - Previous Entries',
        'description' => 'Review and validate yesterday\'s data entries',
        'priority' => 'medium',
        'status' => 'pending',
        'assigned_time' => '13:00:00',
        'estimated_hours' => 2,
        'completed_percentage' => 0
    ],
    [
        'id' => 3,
        'title' => 'Weekly Report Preparation',
        'description' => 'Compile weekly productivity and accuracy report',
        'priority' => 'low',
        'status' => 'pending',
        'assigned_time' => '15:30:00',
        'estimated_hours' => 1,
        'completed_percentage' => 0
    ]
];

// Demo performance metrics
$performanceMetrics = [
    'accuracy_rate' => 98.5,
    'productivity_score' => 92,
    'tasks_completed_today' => 2,
    'tasks_completed_week' => 12,
    'average_task_time' => '2.3 hours',
    'quality_rating' => 4.8
];

// Handle clock in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clock_action'])) {
    $action = $_POST['clock_action'];
    
    switch ($action) {
        case 'clock_in':
            $todayTimesheet['clock_in'] = date('H:i:s');
            $todayTimesheet['status'] = 'active';
            $message = 'Clocked in successfully! Have a productive day.';
            $messageType = 'success';
            break;
            
        case 'clock_out':
            $todayTimesheet['clock_out'] = date('H:i:s');
            $todayTimesheet['status'] = 'completed';
            $message = 'Clocked out successfully! Great work today.';
            $messageType = 'success';
            break;
            
        case 'start_break':
            $todayTimesheet['break_start'] = date('H:i:s');
            $message = 'Break started. Enjoy your break!';
            $messageType = 'info';
            break;
            
        case 'end_break':
            $todayTimesheet['break_end'] = date('H:i:s');
            $message = 'Break ended. Welcome back!';
            $messageType = 'success';
            break;
    }
}

// Calculate worked hours
if ($todayTimesheet['clock_in'] && $todayTimesheet['clock_out']) {
    $clockIn = new DateTime($todayTimesheet['clock_in']);
    $clockOut = new DateTime($todayTimesheet['clock_out']);
    $workedSeconds = $clockOut->getTimestamp() - $clockIn->getTimestamp();
    $todayTimesheet['total_hours'] = round($workedSeconds / 3600, 2);
} elseif ($todayTimesheet['clock_in']) {
    $clockIn = new DateTime($todayTimesheet['clock_in']);
    $now = new DateTime();
    $workedSeconds = $now->getTimestamp() - $clockIn->getTimestamp();
    $todayTimesheet['total_hours'] = round($workedSeconds / 3600, 2);
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - TTS PMS</title>
    
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
        
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .timesheet-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .task-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .task-card:hover {
            border-color: #1266f1;
            box-shadow: 0 2px 10px rgba(18, 102, 241, 0.1);
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
        
        .clock-display {
            font-size: 2.5rem;
            font-weight: 600;
            color: #1266f1;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .status-active {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .status-in_progress {
            background: linear-gradient(135deg, #39c0ed, #0dcaf0);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .performance-meter {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            margin: 0 auto;
        }
        
        .meter-excellent {
            background: conic-gradient(#00b74a 0deg, #00b74a 324deg, #e9ecef 324deg);
        }
        
        .meter-good {
            background: conic-gradient(#28a745 0deg, #28a745 288deg, #e9ecef 288deg);
        }
        
        .meter-average {
            background: conic-gradient(#fbbd08 0deg, #fbbd08 252deg, #e9ecef 252deg);
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Employee'); ?>!</h2>
                    <p class="mb-0">Ready to make today productive? Let's get started with your tasks.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="timesheet.php"><i class="fas fa-clock me-2"></i>Timesheet</a></li>
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
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'info' ? 'info-circle' : 'exclamation-triangle'); ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Time Tracking Card -->
        <div class="card timesheet-card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="clock-display" id="currentTime"><?php echo date('H:i:s'); ?></div>
                        <p class="text-muted mb-0"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="row text-center">
                            <div class="col-4">
                                <h6 class="mb-1">Clock In</h6>
                                <p class="mb-0 text-success"><?php echo $todayTimesheet['clock_in'] ?? '--:--'; ?></p>
                            </div>
                            <div class="col-4">
                                <h6 class="mb-1">Hours Worked</h6>
                                <p class="mb-0 text-primary"><?php echo $todayTimesheet['total_hours']; ?>h</p>
                            </div>
                            <div class="col-4">
                                <h6 class="mb-1">Status</h6>
                                <span class="status-badge status-<?php echo $todayTimesheet['status']; ?>">
                                    <?php echo ucfirst($todayTimesheet['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <form method="POST" class="d-inline">
                            <?php if (!$todayTimesheet['clock_in']): ?>
                            <input type="hidden" name="clock_action" value="clock_in">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-play me-2"></i>Clock In
                            </button>
                            <?php elseif (!$todayTimesheet['clock_out']): ?>
                            <input type="hidden" name="clock_action" value="clock_out">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-stop me-2"></i>Clock Out
                            </button>
                            <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-lg" disabled>
                                <i class="fas fa-check me-2"></i>Day Complete
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Performance Stats -->
            <div class="col-lg-3 mb-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="performance-meter meter-excellent">
                            <?php echo $performanceMetrics['accuracy_rate']; ?>%
                        </div>
                        <h6 class="mt-3 mb-1">Accuracy Rate</h6>
                        <small class="text-muted">This week</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 mb-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-tasks fa-2x text-primary mb-2"></i>
                        <h4 class="mb-1"><?php echo $performanceMetrics['tasks_completed_today']; ?>/<?php echo count($todayTasks); ?></h4>
                        <p class="text-muted mb-0">Tasks Today</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 mb-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                        <h4 class="mb-1"><?php echo $performanceMetrics['productivity_score']; ?>%</h4>
                        <p class="text-muted mb-0">Productivity</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 mb-4">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h4 class="mb-1"><?php echo $performanceMetrics['quality_rating']; ?>/5</h4>
                        <p class="text-muted mb-0">Quality Rating</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Today's Tasks -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Today's Tasks</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($todayTasks as $task): ?>
                        <div class="task-card task-<?php echo $task['priority']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                <span class="status-badge status-<?php echo $task['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </div>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($task['description']); ?></p>
                            
                            <?php if ($task['completed_percentage'] > 0): ?>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar" style="width: <?php echo $task['completed_percentage']; ?>%"></div>
                            </div>
                            <small class="text-muted"><?php echo $task['completed_percentage']; ?>% completed</small>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        Scheduled: <?php echo date('g:i A', strtotime($task['assigned_time'])); ?>
                                    </small>
                                    <span class="mx-2">•</span>
                                    <small class="text-muted">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        Est. <?php echo $task['estimated_hours']; ?>h
                                    </small>
                                </div>
                                <?php if ($task['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-primary">
                                    <i class="fas fa-play me-1"></i>Start Task
                                </button>
                                <?php elseif ($task['status'] === 'in_progress'): ?>
                                <button class="btn btn-sm btn-success">
                                    <i class="fas fa-check me-1"></i>Complete
                                </button>
                                <?php else: ?>
                                <span class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>Completed
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Info -->
            <div class="col-lg-4">
                    </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>This Week</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tasks Completed</span>
                            <strong><?php echo $performanceMetrics['tasks_completed_week']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Avg. Task Time</span>
                            <strong><?php echo $performanceMetrics['average_task_time']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Quality Score</span>
                            <strong><?php echo $performanceMetrics['quality_rating']; ?>/5.0</strong>
                        </div>
                        <hr>
                        <div class="text-center">
                            <button class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-chart-line me-1"></i>View Details
                            </button>
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
        // Update clock every second
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        setInterval(updateClock, 1000);
        updateClock(); // Initial call
    </script>
</body>
</html>
