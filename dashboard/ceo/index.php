<?php
/**
 * TTS PMS - CEO Dashboard
 * Executive dashboard with company-wide analytics and controls
 */

require_once '../../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ceo') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$db = Database::getInstance();
$user = ['first_name' => 'Demo', 'last_name' => 'CEO', 'email' => $_SESSION['email']];

// Executive KPIs
$kpis = [
    'total_revenue' => 2450000,
    'monthly_growth' => 12.5,
    'total_employees' => 45,
    'active_projects' => 23,
    'client_satisfaction' => 94.2,
    'profit_margin' => 18.7
];

$revenueData = [
    ['month' => 'Jan', 'revenue' => 180000, 'profit' => 33600],
    ['month' => 'Feb', 'revenue' => 195000, 'profit' => 36450],
    ['month' => 'Mar', 'revenue' => 210000, 'profit' => 39270],
    ['month' => 'Apr', 'revenue' => 225000, 'profit' => 42075],
    ['month' => 'May', 'revenue' => 240000, 'profit' => 44880],
    ['month' => 'Jun', 'revenue' => 255000, 'profit' => 47685]
];

$departmentPerformance = [
    ['name' => 'Development', 'employees' => 18, 'projects' => 12, 'efficiency' => 92, 'budget' => 450000],
    ['name' => 'Marketing', 'employees' => 8, 'projects' => 6, 'efficiency' => 88, 'budget' => 180000],
    ['name' => 'Sales', 'employees' => 12, 'projects' => 4, 'efficiency' => 95, 'budget' => 220000],
    ['name' => 'Operations', 'employees' => 7, 'projects' => 1, 'efficiency' => 90, 'budget' => 140000]
];

$recentDecisions = [
    ['title' => 'Q2 Budget Approval', 'status' => 'approved', 'date' => '2025-01-18', 'impact' => 'high'],
    ['title' => 'New Client Onboarding', 'status' => 'pending', 'date' => '2025-01-20', 'impact' => 'medium'],
    ['title' => 'Team Expansion Plan', 'status' => 'under_review', 'date' => '2025-01-15', 'impact' => 'high']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEO Dashboard - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #6f42c1, #007bff); color: white; padding: 40px 0; }
        .kpi-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; }
        .kpi-card:hover { transform: translateY(-3px); }
        .chart-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .department-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 15px; }
        .decision-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 15px; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-under_review { background-color: #cce7ff; color: #004085; }
        .impact-high { border-left: 4px solid #dc3545; }
        .impact-medium { border-left: 4px solid #ffc107; }
        .impact-low { border-left: 4px solid #28a745; }
        .metric-positive { color: #28a745; }
        .metric-negative { color: #dc3545; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-3">Executive Dashboard</h1>
                    <p class="lead mb-0">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>! Company overview and strategic insights</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="mb-2">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-calendar me-1"></i><?php echo date('M d, Y'); ?>
                        </span>
                    </div>
                    <a href="../../auth/logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- Key Performance Indicators -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                        <h4>PKR <?php echo number_format($kpis['total_revenue'] / 1000); ?>K</h4>
                        <small class="text-muted">Total Revenue</small>
                        <div class="mt-1">
                            <small class="metric-positive">
                                <i class="fas fa-arrow-up"></i> <?php echo $kpis['monthly_growth']; ?>%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4><?php echo $kpis['total_employees']; ?></h4>
                        <small class="text-muted">Employees</small>
                        <div class="mt-1">
                            <small class="metric-positive">
                                <i class="fas fa-arrow-up"></i> 3 new
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <i class="fas fa-project-diagram fa-2x text-info mb-2"></i>
                        <h4><?php echo $kpis['active_projects']; ?></h4>
                        <small class="text-muted">Active Projects</small>
                        <div class="mt-1">
                            <small class="metric-positive">
                                <i class="fas fa-arrow-up"></i> 2 new
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <i class="fas fa-smile fa-2x text-warning mb-2"></i>
                        <h4><?php echo $kpis['client_satisfaction']; ?>%</h4>
                        <small class="text-muted">Client Satisfaction</small>
                        <div class="mt-1">
                            <small class="metric-positive">
                                <i class="fas fa-arrow-up"></i> 2.1%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                        <h4><?php echo $kpis['profit_margin']; ?>%</h4>
                        <small class="text-muted">Profit Margin</small>
                        <div class="mt-1">
                            <small class="metric-positive">
                                <i class="fas fa-arrow-up"></i> 1.2%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <i class="fas fa-trophy fa-2x text-danger mb-2"></i>
                        <h4>A+</h4>
                        <small class="text-muted">Company Rating</small>
                        <div class="mt-1">
                            <small class="text-muted">Excellent</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Revenue Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card chart-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Revenue & Profit Trends</h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary active">6M</button>
                                <button class="btn btn-outline-primary">1Y</button>
                                <button class="btn btn-outline-primary">All</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Executive Actions -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Executive Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="viewFinancialReport()">
                                <i class="fas fa-chart-bar me-2"></i>Financial Report
                            </button>
                            <button class="btn btn-success" onclick="approvebudget()">
                                <i class="fas fa-check-circle me-2"></i>Approve Budgets
                            </button>
                            <button class="btn btn-info" onclick="reviewStrategy()">
                                <i class="fas fa-chess me-2"></i>Strategic Review
                            </button>
                            <button class="btn btn-warning" onclick="boardMeeting()">
                                <i class="fas fa-users me-2"></i>Board Meeting
                            </button>
                            <button class="btn btn-secondary" onclick="companySettings()">
                                <i class="fas fa-cog me-2"></i>Company Settings
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Quick Stats</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h5 class="text-primary">15</h5>
                                <small class="text-muted">New Clients</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h5 class="text-success">98%</h5>
                                <small class="text-muted">Project Success</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-warning">4.8</h5>
                                <small class="text-muted">Avg Rating</small>
                            </div>
                            <div class="col-6">
                                <h5 class="text-info">32</h5>
                                <small class="text-muted">Countries</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Department Performance -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-building me-2"></i>Department Performance</h4>
                    <button class="btn btn-outline-primary btn-sm" onclick="viewAllDepartments()">
                        <i class="fas fa-expand me-1"></i>View All
                    </button>
                </div>

                <?php foreach ($departmentPerformance as $dept): ?>
                <div class="card department-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><?php echo htmlspecialchars($dept['name']); ?></h6>
                            <span class="badge bg-<?php echo $dept['efficiency'] >= 90 ? 'success' : 'warning'; ?>">
                                <?php echo $dept['efficiency']; ?>% Efficiency
                            </span>
                        </div>
                        <div class="row small text-muted mb-2">
                            <div class="col-4"><?php echo $dept['employees']; ?> employees</div>
                            <div class="col-4"><?php echo $dept['projects']; ?> projects</div>
                            <div class="col-4">PKR <?php echo number_format($dept['budget'] / 1000); ?>K budget</div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-<?php echo $dept['efficiency'] >= 90 ? 'success' : 'warning'; ?>" 
                                 style="width: <?php echo $dept['efficiency']; ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Strategic Decisions -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-chess-king me-2"></i>Strategic Decisions</h4>
                    <button class="btn btn-outline-primary btn-sm" onclick="newDecision()">
                        <i class="fas fa-plus me-1"></i>New Decision
                    </button>
                </div>

                <?php foreach ($recentDecisions as $decision): ?>
                <div class="card decision-card impact-<?php echo $decision['impact']; ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($decision['title']); ?></h6>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($decision['date'])); ?> • 
                                    <?php echo ucfirst($decision['impact']); ?> Impact
                                </small>
                            </div>
                            <span class="badge status-<?php echo $decision['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $decision['status'])); ?>
                            </span>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <?php if ($decision['status'] === 'pending'): ?>
                            <button class="btn btn-success btn-sm" onclick="approveDecision('<?php echo $decision['title']; ?>')">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="rejectDecision('<?php echo $decision['title']; ?>')">
                                <i class="fas fa-times me-1"></i>Reject
                            </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-secondary btn-sm" onclick="viewDecisionDetails('<?php echo $decision['title']; ?>')">
                                <i class="fas fa-eye me-1"></i>Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Company Health Score -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Company Health Score</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block">
                            <canvas id="healthScore" width="120" height="120"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <h3 class="text-success mb-0">92</h3>
                                <small class="text-muted">Excellent</small>
                            </div>
                        </div>
                        <div class="row mt-3 small">
                            <div class="col-4">
                                <div class="text-success">Financial: 95</div>
                            </div>
                            <div class="col-4">
                                <div class="text-warning">Operations: 88</div>
                            </div>
                            <div class="col-4">
                                <div class="text-info">Growth: 93</div>
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
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueData, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (PKR)',
                    data: <?php echo json_encode(array_column($revenueData, 'revenue')); ?>,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Profit (PKR)',
                    data: <?php echo json_encode(array_column($revenueData, 'profit')); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Health Score Doughnut Chart
        const healthCtx = document.getElementById('healthScore').getContext('2d');
        new Chart(healthCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [92, 8],
                    backgroundColor: ['#28a745', '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: false,
                cutout: '80%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });

        // Executive Action Functions
        function viewFinancialReport() {
            alert('Financial report functionality would be implemented here');
        }
        
        function approvebudget() {
            alert('Budget approval functionality would be implemented here');
        }
        
        function reviewStrategy() {
            alert('Strategic review functionality would be implemented here');
        }
        
        function boardMeeting() {
            alert('Board meeting scheduling would be implemented here');
        }
        
        function companySettings() {
            alert('Company settings would be implemented here');
        }
        
        function viewAllDepartments() {
            alert('Department overview would be implemented here');
        }
        
        function newDecision() {
            alert('New strategic decision form would be implemented here');
        }
        
        function approveDecision(title) {
            if (confirm('Approve decision: ' + title + '?')) {
                alert('Decision approved');
            }
        }
        
        function rejectDecision(title) {
            if (confirm('Reject decision: ' + title + '?')) {
                alert('Decision rejected');
            }
        }
        
        function viewDecisionDetails(title) {
            alert('Decision details for: ' + title);
        }
    </script>
</body>
</html>
