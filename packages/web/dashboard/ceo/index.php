<?php
/**
 * TTS PMS - CEO Dashboard
 * Executive overview and strategic management interface
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has CEO role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("CEO dashboard error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Demo executive metrics
$executiveMetrics = [
    'total_revenue' => 2850000, // PKR
    'monthly_growth' => 12.5, // percentage
    'active_clients' => 47,
    'total_employees' => 156,
    'active_projects' => 23,
    'completion_rate' => 94.2,
    'client_satisfaction' => 4.8,
    'profit_margin' => 28.5
];

// Demo financial data
$financialData = [
    'revenue_this_month' => 485000,
    'revenue_last_month' => 432000,
    'expenses_this_month' => 347000,
    'net_profit' => 138000,
    'outstanding_invoices' => 125000,
    'cash_flow' => 'positive'
];

// Demo department performance
$departmentPerformance = [
    [
        'name' => 'Data Analytics',
        'revenue' => 1200000,
        'projects' => 8,
        'employees' => 24,
        'efficiency' => 96.2,
        'growth' => 15.3
    ],
    [
        'name' => 'Data Entry Services',
        'revenue' => 850000,
        'projects' => 12,
        'employees' => 45,
        'efficiency' => 92.8,
        'growth' => 8.7
    ],
    [
        'name' => 'Business Intelligence',
        'revenue' => 650000,
        'projects' => 6,
        'employees' => 18,
        'efficiency' => 94.5,
        'growth' => 22.1
    ],
    [
        'name' => 'Quality Assurance',
        'revenue' => 150000,
        'projects' => 15,
        'employees' => 12,
        'efficiency' => 98.1,
        'growth' => 5.2
    ]
];

// Demo recent activities
$recentActivities = [
    [
        'type' => 'contract_signed',
        'description' => 'New contract signed with ABC Corp - PKR 450,000',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'priority' => 'high'
    ],
    [
        'type' => 'project_completed',
        'description' => 'XYZ Analytics Dashboard project completed successfully',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'priority' => 'medium'
    ],
    [
        'type' => 'employee_milestone',
        'description' => '5 employees completed advanced training certification',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours')),
        'priority' => 'low'
    ],
    [
        'type' => 'client_feedback',
        'description' => 'Received 5-star rating from TechStart Solutions',
        'timestamp' => date('Y-m-d H:i:s', strtotime('-8 hours')),
        'priority' => 'medium'
    ]
];

// Demo alerts and notifications
$alerts = [
    [
        'type' => 'warning',
        'title' => 'Project Deadline Approaching',
        'message' => 'Legacy System Migration project due in 2 days',
        'action_required' => true
    ],
    [
        'type' => 'info',
        'title' => 'Monthly Report Ready',
        'message' => 'October performance report is ready for review',
        'action_required' => false
    ],
    [
        'type' => 'success',
        'title' => 'Revenue Target Achieved',
        'message' => 'Monthly revenue target exceeded by 8.5%',
        'action_required' => false
    ]
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEO Dashboard - TTS PMS</title>
    
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
        
        .dashboard-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .executive-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .executive-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .revenue-card {
            background: linear-gradient(135deg, #00b74a, #28a745);
        }
        
        .growth-card {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
        }
        
        .clients-card {
            background: linear-gradient(135deg, #fbbd08, #ffc107);
        }
        
        .employees-card {
            background: linear-gradient(135deg, #f93154, #dc3545);
        }
        
        .department-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .department-card:hover {
            border-color: #2c3e50;
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.1);
        }
        
        .activity-item {
            border-left: 4px solid #e9ecef;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 10px 10px 0;
            background: white;
        }
        
        .activity-high {
            border-left-color: #f93154;
        }
        
        .activity-medium {
            border-left-color: #fbbd08;
        }
        
        .activity-low {
            border-left-color: #39c0ed;
        }
        
        .alert-card {
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        
        .kpi-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .kpi-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .growth-indicator {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Executive Dashboard</h2>
                    <p class="mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'CEO'); ?>! Here's your company overview.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Executive Reports</a></li>
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

        <!-- Key Performance Indicators -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card revenue-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-number">PKR <?php echo number_format($executiveMetrics['total_revenue'] / 1000000, 1); ?>M</div>
                            <div class="kpi-label">Total Revenue</div>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                    <div class="growth-indicator mt-2">
                        <i class="fas fa-arrow-up me-1"></i><?php echo $executiveMetrics['monthly_growth']; ?>% growth
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card growth-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-number"><?php echo $executiveMetrics['active_clients']; ?></div>
                            <div class="kpi-label">Active Clients</div>
                        </div>
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                    <div class="growth-indicator mt-2">
                        <i class="fas fa-star me-1"></i><?php echo $executiveMetrics['client_satisfaction']; ?>/5 satisfaction
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card clients-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-number"><?php echo $executiveMetrics['total_employees']; ?></div>
                            <div class="kpi-label">Total Employees</div>
                        </div>
                        <i class="fas fa-user-tie fa-2x opacity-75"></i>
                    </div>
                    <div class="growth-indicator mt-2">
                        <i class="fas fa-briefcase me-1"></i><?php echo $executiveMetrics['active_projects']; ?> active projects
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="metric-card employees-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-number"><?php echo $executiveMetrics['profit_margin']; ?>%</div>
                            <div class="kpi-label">Profit Margin</div>
                        </div>
                        <i class="fas fa-percentage fa-2x opacity-75"></i>
                    </div>
                    <div class="growth-indicator mt-2">
                        <i class="fas fa-check me-1"></i><?php echo $executiveMetrics['completion_rate']; ?>% completion rate
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Financial Overview -->
            <div class="col-lg-8">
                <div class="card executive-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Revenue & Growth Analytics</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h6 class="text-success">This Month</h6>
                                <h4 class="text-success">PKR <?php echo number_format($financialData['revenue_this_month']); ?></h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h6 class="text-muted">Last Month</h6>
                                <h4>PKR <?php echo number_format($financialData['revenue_last_month']); ?></h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h6 class="text-warning">Expenses</h6>
                                <h4 class="text-warning">PKR <?php echo number_format($financialData['expenses_this_month']); ?></h4>
                            </div>
                            <div class="col-md-3 text-center">
                                <h6 class="text-primary">Net Profit</h6>
                                <h4 class="text-primary">PKR <?php echo number_format($financialData['net_profit']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Performance -->
                <div class="card executive-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>Department Performance</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($departmentPerformance as $dept): ?>
                        <div class="department-card">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($dept['name']); ?></h6>
                                    <small class="text-muted"><?php echo $dept['employees']; ?> employees</small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <strong class="text-success">PKR <?php echo number_format($dept['revenue'] / 1000); ?>K</strong>
                                    <br><small class="text-muted">Revenue</small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <strong><?php echo $dept['projects']; ?></strong>
                                    <br><small class="text-muted">Projects</small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <strong class="text-primary"><?php echo $dept['efficiency']; ?>%</strong>
                                    <br><small class="text-muted">Efficiency</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="progress mb-1" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $dept['efficiency']; ?>%"></div>
                                    </div>
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up me-1"></i><?php echo $dept['growth']; ?>% growth
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Alerts & Activities -->
            <div class="col-lg-4">
                <!-- Alerts -->
                <div class="card executive-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Executive Alerts</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($alerts as $alert): ?>
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

                <!-- Recent Activities -->
                <div class="card executive-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item activity-<?php echo $activity['priority']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, g:i A', strtotime($activity['timestamp'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $activity['priority'] === 'high' ? 'danger' : ($activity['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                                    <?php echo ucfirst($activity['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card executive-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Executive Actions</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="reports.php">
                                    <i class="fas fa-chart-line me-2"></i>Executive Reports
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="payslip.php">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>Executive Payslip
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
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary">
                                <i class="fas fa-file-alt me-2"></i>Generate Executive Report
                            </button>
                            <button class="btn btn-success">
                                <i class="fas fa-handshake me-2"></i>Review New Contracts
                            </button>
                            <button class="btn btn-warning">
                                <i class="fas fa-users me-2"></i>Team Performance Review
                            </button>
                            <button class="btn btn-info">
                                <i class="fas fa-chart-pie me-2"></i>Financial Analysis
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
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [{
                    label: 'Revenue (PKR)',
                    data: [320000, 385000, 420000, 395000, 445000, 465000, 425000, 475000, 432000, 485000],
                    borderColor: '#1266f1',
                    backgroundColor: 'rgba(18, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Profit (PKR)',
                    data: [95000, 115000, 125000, 118000, 135000, 142000, 128000, 148000, 138000, 152000],
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
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'PKR ' + (value / 1000) + 'K';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
