<?php
/**
 * TTS PMS - Manager Dashboard
 * Team management and oversight interface
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Manager dashboard error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Demo team data
$teamMembers = [
    [
        'id' => 1,
        'name' => 'Alice Johnson',
        'role' => 'Senior Data Analyst',
        'status' => 'active',
        'current_task' => 'Client XYZ Analytics Report',
        'productivity' => 95,
        'accuracy' => 98.5,
        'tasks_today' => 3,
        'hours_worked' => 6.5,
        'last_activity' => '2 minutes ago'
    ],
    [
        'id' => 2,
        'name' => 'Bob Smith',
        'role' => 'Data Entry Specialist',
        'status' => 'active',
        'current_task' => 'Database Migration Project',
        'productivity' => 87,
        'accuracy' => 96.2,
        'tasks_today' => 4,
        'hours_worked' => 7.2,
        'last_activity' => '5 minutes ago'
    ],
    [
        'id' => 3,
        'name' => 'Carol Davis',
        'role' => 'Junior Analyst',
        'status' => 'break',
        'current_task' => 'Quality Control Review',
        'productivity' => 92,
        'accuracy' => 97.8,
        'tasks_today' => 2,
        'hours_worked' => 4.0,
        'last_activity' => '15 minutes ago'
    ],
    [
        'id' => 4,
        'name' => 'David Wilson',
        'role' => 'Data Entry Specialist',
        'status' => 'offline',
        'current_task' => 'Customer Data Processing',
        'productivity' => 89,
        'accuracy' => 95.1,
        'tasks_today' => 5,
        'hours_worked' => 8.0,
        'last_activity' => '1 hour ago'
    ]
];

// Demo project data
$activeProjects = [
    [
        'id' => 1,
        'name' => 'Client ABC Database Migration',
        'progress' => 75,
        'deadline' => date('Y-m-d', strtotime('+5 days')),
        'team_size' => 3,
        'status' => 'on_track',
        'priority' => 'high'
    ],
    [
        'id' => 2,
        'name' => 'XYZ Analytics Dashboard',
        'progress' => 45,
        'deadline' => date('Y-m-d', strtotime('+12 days')),
        'team_size' => 2,
        'status' => 'on_track',
        'priority' => 'medium'
    ],
    [
        'id' => 3,
        'name' => 'Legacy System Data Cleanup',
        'progress' => 30,
        'deadline' => date('Y-m-d', strtotime('+8 days')),
        'team_size' => 4,
        'status' => 'at_risk',
        'priority' => 'high'
    ]
];

// Demo performance metrics
$teamMetrics = [
    'total_team_members' => count($teamMembers),
    'active_members' => count(array_filter($teamMembers, function($m) { return $m['status'] === 'active'; })),
    'avg_productivity' => round(array_sum(array_column($teamMembers, 'productivity')) / count($teamMembers), 1),
    'avg_accuracy' => round(array_sum(array_column($teamMembers, 'accuracy')) / count($teamMembers), 1),
    'total_tasks_today' => array_sum(array_column($teamMembers, 'tasks_today')),
    'projects_on_track' => count(array_filter($activeProjects, function($p) { return $p['status'] === 'on_track'; })),
    'projects_at_risk' => count(array_filter($activeProjects, function($p) { return $p['status'] === 'at_risk'; }))
];

// Handle team actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_action'])) {
    $action = $_POST['team_action'];
    $memberId = (int)($_POST['member_id'] ?? 0);
    
    switch ($action) {
        case 'send_message':
            $message = 'Message sent to team member successfully!';
            $messageType = 'success';
            break;
        case 'assign_task':
            $message = 'Task assigned successfully!';
            $messageType = 'success';
            break;
        case 'approve_leave':
            $message = 'Leave request approved!';
            $messageType = 'success';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - TTS PMS</title>
    
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
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
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
        
        .team-member-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .team-member-card:hover {
            border-color: #6f42c1;
            box-shadow: 0 2px 10px rgba(111, 66, 193, 0.1);
        }
        
        .project-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            border-color: #6f42c1;
            box-shadow: 0 2px 10px rgba(111, 66, 193, 0.1);
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
        
        .status-break {
            background: linear-gradient(135deg, #fbbd08, #ffc107);
            color: white;
        }
        
        .status-offline {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }
        
        .status-on_track {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .status-at_risk {
            background: linear-gradient(135deg, #f93154, #dc3545);
            color: white;
        }
        
        .priority-high {
            border-left: 4px solid #f93154;
        }
        
        .priority-medium {
            border-left: 4px solid #fbbd08;
        }
        
        .priority-low {
            border-left: 4px solid #39c0ed;
        }
        
        .productivity-meter {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
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
                    <h2 class="mb-1">Manager Dashboard</h2>
                    <p class="mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Manager'); ?>! Monitor your team's performance and manage projects.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
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

        <!-- Team Overview Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4 class="mb-1"><?php echo $teamMetrics['active_members']; ?>/<?php echo $teamMetrics['total_team_members']; ?></h4>
                        <p class="text-muted mb-0">Active Team</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                        <h4 class="mb-1"><?php echo $teamMetrics['avg_productivity']; ?>%</h4>
                        <p class="text-muted mb-0">Avg Productivity</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-bullseye fa-2x text-warning mb-2"></i>
                        <h4 class="mb-1"><?php echo $teamMetrics['avg_accuracy']; ?>%</h4>
                        <p class="text-muted mb-0">Avg Accuracy</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-tasks fa-2x text-info mb-2"></i>
                        <h4 class="mb-1"><?php echo $teamMetrics['total_tasks_today']; ?></h4>
                        <p class="text-muted mb-0">Tasks Today</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Team Members -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Members</h5>
                        <button class="btn btn-sm btn-primary" data-mdb-toggle="modal" data-mdb-target="#assignTaskModal">
                            <i class="fas fa-plus me-1"></i>Assign Task
                        </button>
                    </div>
                    <div class="card-body">
                        <?php foreach ($teamMembers as $member): ?>
                        <div class="team-member-card">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <div class="productivity-meter <?php echo $member['productivity'] >= 90 ? 'meter-excellent' : ($member['productivity'] >= 80 ? 'meter-good' : 'meter-average'); ?> me-3">
                                            <?php echo $member['productivity']; ?>%
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($member['name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($member['role']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-1">Current Task</h6>
                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars($member['current_task']); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Tasks</small>
                                            <strong><?php echo $member['tasks_today']; ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Hours</small>
                                            <strong><?php echo $member['hours_worked']; ?>h</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <span class="status-badge status-<?php echo $member['status']; ?> d-block mb-2">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-mdb-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-comment me-2"></i>Send Message</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-tasks me-2"></i>Assign Task</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-chart-bar me-2"></i>View Performance</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Projects & Quick Actions -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i>Active Projects</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($activeProjects as $project): ?>
                        <div class="project-card priority-<?php echo $project['priority']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1"><?php echo htmlspecialchars($project['name']); ?></h6>
                                <span class="status-badge status-<?php echo $project['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>
                            </div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar" style="width: <?php echo $project['progress']; ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i><?php echo $project['team_size']; ?> members
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('M j', strtotime($project['deadline'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-plus me-2"></i>Create New Project
                            </button>
                            <button class="btn btn-outline-success">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                            <button class="btn btn-outline-warning">
                                <i class="fas fa-calendar-check me-2"></i>Schedule Meeting
                            </button>
                            <button class="btn btn-outline-info">
                                <i class="fas fa-bullhorn me-2"></i>Team Announcement
                            </button>
                        </div>
                        
                        <hr class="my-3">
                        
                        <h6 class="mb-3">Pending Approvals</h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Leave Requests</span>
                            <span class="badge bg-warning">2</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Overtime Requests</span>
                            <span class="badge bg-info">1</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small">Expense Reports</span>
                            <span class="badge bg-secondary">3</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Task Modal -->
    <div class="modal fade" id="assignTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign New Task</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="team_action" value="assign_task">
                        
                        <div class="mb-3">
                            <label for="assignee" class="form-label">Assign To</label>
                            <select class="form-select" id="assignee" name="member_id" required>
                                <option value="">Select team member</option>
                                <?php foreach ($teamMembers as $member): ?>
                                <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskTitle" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="taskTitle" name="task_title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="taskDescription" name="task_description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="dueDate" class="form-label">Due Date</label>
                                <input type="date" class="form-control" id="dueDate" name="due_date" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
</body>
</html>
