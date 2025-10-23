<?php
require_once '../config/init.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance();

// Get report data
$employeeStats = $db->fetchAll("
    SELECT 
        e.role,
        COUNT(*) as count,
        AVG(e.salary) as avg_salary
    FROM tts_employment e 
    WHERE e.status = 'active' 
    GROUP BY e.role
");

$payrollStats = $db->fetchAll("
    SELECT 
        DATE_FORMAT(week_start, '%Y-%m') as month,
        COUNT(*) as payroll_count,
        SUM(final_amount) as total_amount
    FROM tts_payroll_weeks 
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(week_start, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");

$recentActivity = $db->fetchAll("
    SELECT 'Application' as type, business_name as title, created_at 
    FROM tts_applications 
    UNION ALL
    SELECT 'Proposal' as type, business_name as title, created_at 
    FROM tts_proposal_requests
    ORDER BY created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #1266f1, #39c0ed); min-height: 100vh; width: 250px; position: fixed; }
        .main-content { margin-left: 250px; padding: 20px; }
        .report-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        @media (max-width: 768px) { .sidebar { margin-left: -250px; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4"><i class="fas fa-shield-alt me-2"></i>TTS Admin</h4>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link text-white mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="applications.php" class="nav-link text-white mb-2"><i class="fas fa-file-alt me-2"></i>Applications</a>
                <a href="employees.php" class="nav-link text-white mb-2"><i class="fas fa-users me-2"></i>Employees</a>
                <a href="payroll-automation.php" class="nav-link text-white mb-2"><i class="fas fa-money-bill-wave me-2"></i>Payroll</a>
                <a href="leaves.php" class="nav-link text-white mb-2"><i class="fas fa-calendar-times me-2"></i>Leaves</a>
                <a href="clients.php" class="nav-link text-white mb-2"><i class="fas fa-handshake me-2"></i>Clients</a>
                <a href="proposals.php" class="nav-link text-white mb-2"><i class="fas fa-file-contract me-2"></i>Proposals</a>
                <a href="gigs.php" class="nav-link text-white mb-2"><i class="fas fa-briefcase me-2"></i>Gigs</a>
                <a href="reports.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                <a href="settings.php" class="nav-link text-white mb-2"><i class="fas fa-cog me-2"></i>Settings</a>
                <hr class="text-white">
                <a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </nav>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Reports & Analytics</h2>
                <p class="text-muted mb-0">System performance and business insights</p>
            </div>
            <button class="btn btn-primary" onclick="exportReport()">
                <i class="fas fa-download me-2"></i>Export Report
            </button>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Payroll Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="payrollChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Employee Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="employeeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-activity me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <span class="badge bg-<?php echo $activity['type'] === 'Application' ? 'primary' : 'success'; ?> me-2">
                                    <?php echo $activity['type']; ?>
                                </span>
                                <?php echo htmlspecialchars($activity['title']); ?>
                            </div>
                            <small class="text-muted"><?php echo date('M d', strtotime($activity['created_at'])); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card report-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $totalPayroll = $db->fetchOne("SELECT SUM(final_amount) as total FROM tts_payroll_weeks WHERE status = 'completed'")['total'] ?? 0;
                        $monthlyPayroll = $db->fetchOne("SELECT SUM(final_amount) as total FROM tts_payroll_weeks WHERE status = 'completed' AND MONTH(week_start) = MONTH(CURRENT_DATE())")['total'] ?? 0;
                        ?>
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary">PKR <?php echo number_format($totalPayroll); ?></h4>
                                <small class="text-muted">Total Payroll</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">PKR <?php echo number_format($monthlyPayroll); ?></h4>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    <script>
        // Payroll Chart
        const payrollCtx = document.getElementById('payrollChart').getContext('2d');
        new Chart(payrollCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_reverse(array_column($payrollStats, 'month'))); ?>,
                datasets: [{
                    label: 'Monthly Payroll (PKR)',
                    data: <?php echo json_encode(array_reverse(array_column($payrollStats, 'total_amount'))); ?>,
                    borderColor: '#1266f1',
                    backgroundColor: 'rgba(18, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Employee Chart
        const employeeCtx = document.getElementById('employeeChart').getContext('2d');
        new Chart(employeeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($employeeStats, 'role')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($employeeStats, 'count')); ?>,
                    backgroundColor: ['#1266f1', '#00b74a', '#fbbd08', '#f93154']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });

        function exportReport() {
            alert('Export functionality would be implemented here');
        }
    </script>
</body>
</html>
