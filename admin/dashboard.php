<?php
/**
 * TTS PMS - Admin Dashboard
 * Main administrative interface
 */

// Load configuration
require_once '../config/init.php';

// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Demo admin statistics
$adminStats = [
    'total_users' => 156,
    'new_users_today' => 8,
    'active_sessions' => 42,
    'total_applications' => 89,
    'pending_applications' => 12,
    'approved_applications' => 67,
    'rejected_applications' => 10,
    'total_leaves' => 34,
    'pending_leaves' => 5,
    'total_proposals' => 23,
    'active_proposals' => 8,
    'system_uptime' => '99.8%',
    'database_size' => '2.4 GB',
    'server_load' => '23%'
];

// Demo recent activities
$recentActivities = [
    [
        'type' => 'user_registration',
        'description' => 'New user registered: john.doe@example.com',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
        'severity' => 'info'
    ],
    [
        'type' => 'application_submitted',
        'description' => 'Job application submitted for Data Analyst position',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-32 minutes')),
        'severity' => 'info'
    ],
    [
        'type' => 'leave_approved',
        'description' => 'Leave request approved for Sarah Khan (3 days)',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'severity' => 'success'
    ],
    [
        'type' => 'proposal_received',
        'description' => 'New business proposal from TechStart Solutions',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'severity' => 'warning'
    ],
    [
        'type' => 'system_backup',
        'description' => 'Automated system backup completed successfully',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'severity' => 'success'
    ]
];

// Demo system alerts
$systemAlerts = [
    [
        'type' => 'warning',
        'title' => 'High Server Load',
        'message' => 'Server CPU usage is above 80% for the last 30 minutes',
        'action_required' => true
    ],
    [
        'type' => 'info',
        'title' => 'Scheduled Maintenance',
        'message' => 'System maintenance scheduled for tonight at 2:00 AM',
        'action_required' => false
    ],
    [
        'type' => 'success',
        'title' => 'Backup Completed',
        'message' => 'Daily database backup completed successfully',
        'action_required' => false
    ]
];

// Demo pending approvals
$pendingApprovals = [
    [
        'type' => 'application',
        'title' => 'Job Application - Data Entry Specialist',
        'applicant' => 'Ahmed Hassan',
        'submitted' => date('Y-m-d', strtotime('-2 days')),
        'priority' => 'medium'
    ],
    [
        'type' => 'leave',
        'title' => 'Annual Leave Request',
        'applicant' => 'Maria Garcia',
        'submitted' => date('Y-m-d', strtotime('-1 day')),
        'priority' => 'high'
    ],
    [
        'type' => 'proposal',
        'title' => 'Business Proposal - Data Analytics',
        'applicant' => 'XYZ Corporation',
        'submitted' => date('Y-m-d', strtotime('-3 days')),
        'priority' => 'high'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TTS PMS</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-primary { background: linear-gradient(135deg, #1266f1, #39c0ed); }
        .stat-success { background: linear-gradient(135deg, #00b74a, #28a745); }
        .stat-warning { background: linear-gradient(135deg, #fbbd08, #ffc107); }
        .stat-danger { background: linear-gradient(135deg, #f93154, #dc3545); }
        .stat-info { background: linear-gradient(135deg, #39c0ed, #0dcaf0); }
        .stat-secondary { background: linear-gradient(135deg, #6c757d, #495057); }
        
        .activity-item {
            border-left: 4px solid #e9ecef;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 10px 10px 0;
            background: white;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .activity-info { border-left-color: #39c0ed; }
        .activity-success { border-left-color: #00b74a; }
        .activity-warning { border-left-color: #fbbd08; }
        .activity-danger { border-left-color: #f93154; }
        
        .alert-card {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .approval-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .approval-item:hover {
            border-color: #dc3545;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.1);
        }
        
        .priority-high { border-left: 4px solid #f93154; }
        .priority-medium { border-left: 4px solid #fbbd08; }
        .priority-low { border-left: 4px solid #39c0ed; }
        
        .quick-action-btn {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: block;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Admin Dashboard</h2>
                    <p class="mb-0">System administration and management console</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i>Administrator
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>System Settings</a></li>
                            <li><a class="dropdown-item" href="logs.php"><i class="fas fa-file-alt me-2"></i>System Logs</a></li>
                            <li><a class="dropdown-item" href="backup.php"><i class="fas fa-database me-2"></i>Backup & Restore</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
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

        <!-- System Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-icon stat-primary mx-auto">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <h4 class="mb-1"><?php echo number_format($adminStats['total_users']); ?></h4>
                        <p class="text-muted mb-0">Total Users</p>
                        <small class="text-success">+<?php echo $adminStats['new_users_today']; ?> today</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-icon stat-success mx-auto">
                            <i class="fas fa-file-alt text-white"></i>
                        </div>
                        <h4 class="mb-1"><?php echo $adminStats['total_applications']; ?></h4>
                        <p class="text-muted mb-0">Applications</p>
                        <small class="text-warning"><?php echo $adminStats['pending_applications']; ?> pending</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-icon stat-warning mx-auto">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                        <h4 class="mb-1"><?php echo $adminStats['total_leaves']; ?></h4>
                        <p class="text-muted mb-0">Leave Requests</p>
                        <small class="text-info"><?php echo $adminStats['pending_leaves']; ?> pending</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-icon stat-info mx-auto">
                            <i class="fas fa-handshake text-white"></i>
                        </div>
                        <h4 class="mb-1"><?php echo $adminStats['total_proposals']; ?></h4>
                        <p class="text-muted mb-0">Proposals</p>
                        <small class="text-success"><?php echo $adminStats['active_proposals']; ?> active</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-icon stat-secondary mx-auto">
                            <i class="fas fa-server text-white"></i>
                        </div>
                        <h4 class="mb-1"><?php echo $adminStats['system_uptime']; ?></h4>
                        <p class="text-muted mb-0">Uptime</p>
                        <small class="text-success">Excellent</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <div class="stat-icon stat-danger mx-auto">
                            <i class="fas fa-tachometer-alt text-white"></i>
                        </div>
                        <h4 class="mb-1"><?php echo $adminStats['server_load']; ?></h4>
                        <p class="text-muted mb-0">Server Load</p>
                        <small class="text-success">Normal</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- System Analytics Chart -->
                <div class="card stats-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>System Analytics</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="systemChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="card stats-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent System Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item activity-<?php echo $activity['severity']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $activity['severity'] === 'info' ? 'primary' : $activity['severity']; ?>">
                                    <?php echo ucfirst($activity['type']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- System Alerts -->
                <div class="card stats-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>System Alerts</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($systemAlerts as $alert): ?>
                        <div class="alert alert-<?php echo $alert['type']; ?> alert-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="alert-heading mb-1"><?php echo htmlspecialchars($alert['title']); ?></h6>
                                    <p class="mb-0 small"><?php echo htmlspecialchars($alert['message']); ?></p>
                                </div>
                                <?php if ($alert['action_required']): ?>
                                <span class="badge bg-danger">Action Required</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="card stats-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Pending Approvals</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($pendingApprovals as $approval): ?>
                        <div class="approval-item priority-<?php echo $approval['priority']; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-1"><?php echo htmlspecialchars($approval['title']); ?></h6>
                                <span class="badge bg-<?php echo $approval['priority'] === 'high' ? 'danger' : ($approval['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                    <?php echo ucfirst($approval['priority']); ?>
                                </span>
                            </div>
                            <p class="mb-2 small text-muted">
                                <strong>From:</strong> <?php echo htmlspecialchars($approval['applicant']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('M j, Y', strtotime($approval['submitted'])); ?>
                                </small>
                                <div>
                                    <button class="btn btn-sm btn-success me-1">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card stats-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body p-2">
                        <a href="applications.php" class="quick-action-btn stat-primary">
                            <i class="fas fa-file-alt fa-lg mb-2"></i>
                            <div>Manage Applications</div>
                        </a>
                        <a href="leaves.php" class="quick-action-btn stat-warning">
                            <i class="fas fa-calendar-alt fa-lg mb-2"></i>
                            <div>Review Leave Requests</div>
                        </a>
                        <a href="proposals.php" class="quick-action-btn stat-success">
                            <i class="fas fa-handshake fa-lg mb-2"></i>
                            <div>Business Proposals</div>
                        </a>
                        <a href="reports.php" class="quick-action-btn stat-info">
                            <i class="fas fa-chart-bar fa-lg mb-2"></i>
                            <div>Generate Reports</div>
                        </a>
                        <a href="settings.php" class="quick-action-btn stat-secondary">
                            <i class="fas fa-cog fa-lg mb-2"></i>
                            <div>System Settings</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // System Analytics Chart
        const ctx = document.getElementById('systemChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                datasets: [{
                    label: 'Active Users',
                    data: [12, 8, 25, 42, 38, 28, 15],
                    borderColor: '#1266f1',
                    backgroundColor: 'rgba(18, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Server Load (%)',
                    data: [15, 12, 28, 35, 42, 38, 23],
                    borderColor: '#f93154',
                    backgroundColor: 'rgba(249, 49, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Database Queries',
                    data: [45, 32, 78, 125, 98, 67, 42],
                    borderColor: '#00b74a',
                    backgroundColor: 'rgba(0, 183, 74, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
