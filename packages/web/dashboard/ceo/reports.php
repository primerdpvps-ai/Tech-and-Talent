<?php
/**
 * TTS PMS - CEO Reports
 * Executive reports and analytics dashboard
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has CEO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ceo') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'CEO Reports';
$currentPage = 'reports';

// Get analytics data
try {
    $db = Database::getInstance();
    
    // User statistics
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users")['count'] ?? 0;
    $activeEmployees = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE role IN ('employee', 'manager') AND status = 'ACTIVE'")['count'] ?? 0;
    $pendingCandidates = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE role = 'candidate' AND status = 'PENDING_VERIFICATION'")['count'] ?? 0;
    
    // Job statistics
    $openJobs = $db->fetchOne("SELECT COUNT(*) as count FROM tts_job_positions WHERE status = 'open'")['count'] ?? 0;
    $totalApplications = $db->fetchOne("SELECT COUNT(*) as count FROM tts_job_applications")['count'] ?? 0;
    
    // Gig statistics
    $activeGigs = $db->fetchOne("SELECT COUNT(*) as count FROM tts_gigs WHERE status = 'in_progress'")['count'] ?? 0;
    $completedGigs = $db->fetchOne("SELECT COUNT(*) as count FROM tts_gigs WHERE status = 'completed'")['count'] ?? 0;
    
    // Recent activities
    $recentApplications = $db->fetchAll("
        SELECT ja.*, jp.title as job_title, u.first_name, u.last_name, u.email
        FROM tts_job_applications ja
        JOIN tts_job_positions jp ON ja.job_position_id = jp.id
        JOIN tts_users u ON ja.user_id = u.id
        ORDER BY ja.created_at DESC
        LIMIT 10
    ");
    
    // Monthly user registrations
    $monthlyUsers = $db->fetchAll("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
        FROM tts_users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    
} catch (Exception $e) {
    $totalUsers = $activeEmployees = $pendingCandidates = 0;
    $openJobs = $totalApplications = $activeGigs = $completedGigs = 0;
    $recentApplications = $monthlyUsers = [];
}
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card .card-body {
            padding: 2rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-crown me-2"></i>CEO Panel
            </h4>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="../../../../admin/dashboard.php">
                        <i class="fas fa-shield-alt me-2"></i>Admin Panel
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
            <h2><i class="fas fa-chart-bar me-2"></i>CEO Reports & Analytics</h2>
            <div class="text-muted">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-3"></i>
                        <div class="stat-number"><?php echo $totalUsers; ?></div>
                        <div>Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie fa-2x mb-3"></i>
                        <div class="stat-number"><?php echo $activeEmployees; ?></div>
                        <div>Active Employees</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-briefcase fa-2x mb-3"></i>
                        <div class="stat-number"><?php echo $openJobs; ?></div>
                        <div>Open Positions</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tasks fa-2x mb-3"></i>
                        <div class="stat-number"><?php echo $activeGigs; ?></div>
                        <div>Active Gigs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line me-2"></i>Monthly User Growth</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>User Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="userDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Recent Job Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentApplications)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No recent applications found</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th>Applied Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentApplications as $application): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($application['email']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($application['status']) {
                                                        'submitted' => 'primary',
                                                        'under_review' => 'warning',
                                                        'interview_scheduled' => 'info',
                                                        'accepted' => 'success',
                                                        'rejected' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($application['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyUsers); ?>;
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'New Users',
                    data: monthlyData.map(item => item.count),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // User Distribution Chart
        const userDistCtx = document.getElementById('userDistributionChart').getContext('2d');
        
        new Chart(userDistCtx, {
            type: 'doughnut',
            data: {
                labels: ['Employees', 'Candidates', 'Managers', 'Others'],
                datasets: [{
                    data: [<?php echo $activeEmployees; ?>, <?php echo $pendingCandidates; ?>, 2, <?php echo max(0, $totalUsers - $activeEmployees - $pendingCandidates - 2); ?>],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5f7fa'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
