<?php
/**
 * TTS PMS - Manager Reports
 * Team reports and analytics for managers
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has manager role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'Team Reports';
$currentPage = 'reports';

// Get team analytics
try {
    $db = Database::getInstance();
    
    // Team member count
    $totalTeam = $db->fetchOne("SELECT COUNT(*) as count FROM tts_users WHERE role IN ('employee', 'new_employee') AND status = 'ACTIVE'")['count'] ?? 0;
    
    // Today's attendance
    $todayAttendance = $db->fetchAll("
        SELECT u.first_name, u.last_name, u.email, te.clock_in, te.clock_out, te.total_hours, te.status
        FROM tts_users u
        LEFT JOIN tts_time_entries te ON u.id = te.user_id AND te.date = CURDATE()
        WHERE u.role IN ('employee', 'new_employee') AND u.status = 'ACTIVE'
        ORDER BY u.first_name, u.last_name
    ");
    
    // Weekly hours summary
    $weeklyHours = $db->fetchAll("
        SELECT u.first_name, u.last_name, SUM(te.total_hours) as weekly_hours
        FROM tts_users u
        LEFT JOIN tts_time_entries te ON u.id = te.user_id 
            AND te.date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
        WHERE u.role IN ('employee', 'new_employee') AND u.status = 'ACTIVE'
        GROUP BY u.id, u.first_name, u.last_name
        ORDER BY weekly_hours DESC
    ");
    
    // Task completion rates
    $taskStats = $db->fetchAll("
        SELECT 
            u.first_name, u.last_name,
            COUNT(dt.id) as total_tasks,
            SUM(CASE WHEN dt.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM tts_users u
        LEFT JOIN tts_daily_tasks dt ON u.id = dt.user_id AND dt.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE u.role IN ('employee', 'new_employee') AND u.status = 'ACTIVE'
        GROUP BY u.id, u.first_name, u.last_name
        ORDER BY u.first_name, u.last_name
    ");
    
} catch (Exception $e) {
    $totalTeam = 0;
    $todayAttendance = [];
    $weeklyHours = [];
    $taskStats = [];
}

// Calculate stats
$presentToday = count(array_filter($todayAttendance, fn($att) => !empty($att['clock_in'])));
$activeNow = count(array_filter($todayAttendance, fn($att) => $att['status'] === 'active'));
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
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
        
        .stat-card {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-user-tie me-2"></i>Manager
            </h4>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-2"></i>My Profile
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
            <h2><i class="fas fa-chart-bar me-2"></i>Team Reports</h2>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <h3><?php echo $totalTeam; ?></h3>
                    <div>Team Members</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <h3><?php echo $presentToday; ?></h3>
                    <div>Present Today</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <h3><?php echo $activeNow; ?></h3>
                    <div>Currently Active</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <h3><?php echo round(($presentToday / max($totalTeam, 1)) * 100); ?>%</h3>
                    <div>Attendance Rate</div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Today's Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Clock In</th>
                                        <th>Clock Out</th>
                                        <th>Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todayAttendance as $attendance): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($attendance['email']); ?></small>
                                        </td>
                                        <td><?php echo $attendance['clock_in'] ? date('g:i A', strtotime($attendance['clock_in'])) : '-'; ?></td>
                                        <td><?php echo $attendance['clock_out'] ? date('g:i A', strtotime($attendance['clock_out'])) : '-'; ?></td>
                                        <td><?php echo $attendance['total_hours'] ? number_format($attendance['total_hours'], 1) . 'h' : '-'; ?></td>
                                        <td>
                                            <?php if ($attendance['clock_in']): ?>
                                                <span class="badge bg-<?php 
                                                    echo match($attendance['status']) {
                                                        'active' => 'success',
                                                        'break' => 'warning',
                                                        'completed' => 'primary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($attendance['status'] ?? 'Unknown'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Absent</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Hours Chart -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie me-2"></i>Weekly Hours</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyHoursChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Performance -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tasks me-2"></i>Task Performance (Last 30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Total Tasks</th>
                                        <th>Completed</th>
                                        <th>Completion Rate</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($taskStats as $stat): ?>
                                    <?php 
                                    $completionRate = $stat['total_tasks'] > 0 ? round(($stat['completed_tasks'] / $stat['total_tasks']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($stat['first_name'] . ' ' . $stat['last_name']); ?></strong></td>
                                        <td><?php echo $stat['total_tasks']; ?></td>
                                        <td><?php echo $stat['completed_tasks']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress me-2" style="width: 100px; height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $completionRate >= 80 ? 'success' : ($completionRate >= 60 ? 'warning' : 'danger'); ?>" 
                                                         style="width: <?php echo $completionRate; ?>%"></div>
                                                </div>
                                                <span><?php echo $completionRate; ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($completionRate >= 80): ?>
                                                <span class="badge bg-success">Excellent</span>
                                            <?php elseif ($completionRate >= 60): ?>
                                                <span class="badge bg-warning">Good</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Needs Improvement</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Weekly Hours Chart
        const weeklyData = <?php echo json_encode($weeklyHours); ?>;
        const ctx = document.getElementById('weeklyHoursChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: weeklyData.map(item => item.first_name + ' ' + item.last_name),
                datasets: [{
                    data: weeklyData.map(item => parseFloat(item.weekly_hours) || 0),
                    backgroundColor: [
                        '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', 
                        '#6610f2', '#e91e63', '#ff9800', '#4caf50'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
