<?php
/**
 * TTS PMS - Super Admin Panel
 * Main dashboard for system administration
 */

// Load configuration and check admin access
require_once '../config/init.php';

// Start session
session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get database connection
$db = Database::getInstance();

// Fetch dashboard statistics
try {
    // Total users by role
    $stats = [
        'total_users' => $db->count('tts_users_meta'),
        'total_employees' => $db->count('tts_employment'),
        'pending_applications' => $db->count('tts_applications', 'status = ?', ['under_review']),
        'active_sessions' => $db->count('tts_timer_sessions', 'ended_at IS NULL'),
        'pending_leaves' => $db->count('tts_leaves', 'status = ?', ['pending']),
        'this_week_payroll' => 0,
        'client_requests' => $db->count('tts_client_requests', 'status = ?', ['new']),
        'proposal_requests' => $db->count('tts_proposal_requests', 'status = ?', ['pending'])
    ];
    
    // Calculate this week's payroll total
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime('sunday this week'));
    
    $payroll_result = $db->fetchOne(
        'SELECT SUM(final_amount) as total FROM tts_payroll_weeks WHERE week_start = ? AND status != ?',
        [$week_start, 'paid']
    );
    $stats['this_week_payroll'] = $payroll_result['total'] ?? 0;
    
    // Recent activities
    $recent_applications = $db->fetchAll(
        'SELECT a.*, um.user_id FROM tts_applications a 
         JOIN tts_users_meta um ON a.user_id = um.user_id 
         WHERE a.status = ? ORDER BY a.submitted_at DESC LIMIT 5',
        ['under_review']
    );
    
    $recent_leaves = $db->fetchAll(
        'SELECT l.*, um.user_id FROM tts_leaves l 
         JOIN tts_users_meta um ON l.user_id = um.user_id 
         WHERE l.status = ? ORDER BY l.requested_at DESC LIMIT 5',
        ['pending']
    );
    
    $recent_clients = $db->fetchAll(
        'SELECT * FROM tts_client_requests 
         WHERE status = ? ORDER BY created_at DESC LIMIT 5',
        ['new']
    );
    
} catch (Exception $e) {
    log_message('error', 'Admin dashboard data fetch failed: ' . $e->getMessage());
    $stats = array_fill_keys(['total_users', 'total_employees', 'pending_applications', 'active_sessions', 'pending_leaves', 'this_week_payroll', 'client_requests', 'proposal_requests'], 0);
    $recent_applications = $recent_leaves = $recent_clients = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Super Admin Dashboard</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #6c757d;
            --success-color: #00b74a;
            --danger-color: #f93154;
            --warning-color: #fbbd08;
            --info-color: #39c0ed;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-color);
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 0;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
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
            color: white;
        }
        
        .table-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .badge-status {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-shield-alt me-2"></i>TTS Admin</h4>
                        <small>Super Admin Panel</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="applications.php">
                            <i class="fas fa-file-alt me-2"></i>Applications
                            <?php if ($stats['pending_applications'] > 0): ?>
                            <span class="badge bg-warning ms-auto"><?php echo $stats['pending_applications']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="employees.php">
                            <i class="fas fa-users me-2"></i>Employees
                        </a>
                        <a class="nav-link" href="payroll.php">
                            <i class="fas fa-money-bill-wave me-2"></i>Payroll
                        </a>
                        <a class="nav-link" href="leaves.php">
                            <i class="fas fa-calendar-times me-2"></i>Leave Requests
                            <?php if ($stats['pending_leaves'] > 0): ?>
                            <span class="badge bg-info ms-auto"><?php echo $stats['pending_leaves']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="clients.php">
                            <i class="fas fa-handshake me-2"></i>Client Requests
                            <?php if ($stats['client_requests'] > 0): ?>
                            <span class="badge bg-success ms-auto"><?php echo $stats['client_requests']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="proposals.php">
                            <i class="fas fa-paper-plane me-2"></i>Proposals
                            <?php if ($stats['proposal_requests'] > 0): ?>
                            <span class="badge bg-primary ms-auto"><?php echo $stats['proposal_requests']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="gigs.php">
                            <i class="fas fa-briefcase me-2"></i>Services/Gigs
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        
                        <hr class="my-3">
                        
                        <a class="nav-link" href="../" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Website
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Dashboard Overview</h2>
                            <p class="text-muted mb-0">Welcome back, Administrator</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                                    <i class="fas fa-plus me-1"></i>Quick Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="applications.php">Review Applications</a></li>
                                    <li><a class="dropdown-item" href="payroll.php">Process Payroll</a></li>
                                    <li><a class="dropdown-item" href="employees.php">Add Employee</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="settings.php">System Settings</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-primary me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['total_employees']); ?></h3>
                                        <p class="text-muted mb-0">Total Employees</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-warning me-3">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['pending_applications']); ?></h3>
                                        <p class="text-muted mb-0">Pending Applications</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-success me-3">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1"><?php echo number_format($stats['active_sessions']); ?></h3>
                                        <p class="text-muted mb-0">Active Sessions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="card stat-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stat-icon bg-info me-3">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1">Rs. <?php echo number_format($stats['this_week_payroll']); ?></h3>
                                        <p class="text-muted mb-0">This Week Payroll</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card table-card">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">Weekly Performance Overview</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="performanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card table-card">
                                <div class="card-header bg-transparent">
                                    <h5 class="mb-0">Employee Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="employeeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activities -->
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="card table-card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Applications</h5>
                                    <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recent_applications)): ?>
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p class="mb-0">No pending applications</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_applications as $app): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">User #<?php echo $app['user_id']; ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($app['job_type']); ?>-time position</small>
                                            </div>
                                            <span class="badge badge-status bg-warning">Pending</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card table-card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Leave Requests</h5>
                                    <a href="leaves.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recent_leaves)): ?>
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                        <p class="mb-0">No pending leave requests</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_leaves as $leave): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">User #<?php echo $leave['user_id']; ?></h6>
                                                <small class="text-muted"><?php echo ucfirst($leave['type']); ?> leave</small>
                                            </div>
                                            <span class="badge badge-status bg-info">Pending</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card table-card">
                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Client Requests</h5>
                                    <a href="clients.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recent_clients)): ?>
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-handshake fa-2x mb-2"></i>
                                        <p class="mb-0">No new client requests</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_clients as $client): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($client['business_name']); ?></h6>
                                                <span class="badge badge-status bg-success">New</span>
                                            </div>
                                            <small class="text-muted"><?php echo htmlspecialchars($client['contact_person']); ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
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
        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Active Hours',
                    data: [120, 135, 142, 128, 156, 89, 67],
                    borderColor: '#1266f1',
                    backgroundColor: 'rgba(18, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Employees Online',
                    data: [15, 18, 19, 16, 20, 12, 8],
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
        
        // Employee Distribution Chart
        const employeeCtx = document.getElementById('employeeChart').getContext('2d');
        new Chart(employeeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Full-time', 'Part-time', 'New Employees', 'Managers'],
                datasets: [{
                    data: [45, 25, 8, 3],
                    backgroundColor: [
                        '#1266f1',
                        '#39c0ed',
                        '#fbbd08',
                        '#00b74a'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Refresh dashboard function
        function refreshDashboard() {
            location.reload();
        }
        
        // Auto-refresh every 5 minutes
        setInterval(refreshDashboard, 300000);
    </script>
</body>
</html>
