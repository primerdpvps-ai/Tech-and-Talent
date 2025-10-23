<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection status
$dbConnected = false;
$dbError = '';

try {
    require_once '../../config/init.php';
    
    // Test database connection
    try {
        $db = Database::getInstance();
        $dbConnected = $db->testConnection();
        if (!$dbConnected) {
            $dbError = 'Database connection test failed';
        }
    } catch (Exception $e) {
        $dbError = 'Database error: ' . $e->getMessage();
    }
    
} catch (Exception $e) {
    $dbError = 'Configuration error: ' . $e->getMessage();
}

// Start session
session_start();
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>TTS PMS - Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- MDB Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #6c757d;
            --success-color: #00b74a;
            --danger-color: #f93154;
            --warning-color: #fbbd08;
            --info-color: #39c0ed;
            
            /* Light Theme */
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }
        
        [data-mdb-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #e9ecef;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(18, 102, 241, 0.95) !important;
        }
        
        .card {
            background-color: var(--bg-primary);
            border-color: var(--border-color);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem var(--shadow-color);
        }
        
        .stat-card {
            border-left: 4px solid;
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        
        .sidebar {
            background-color: var(--bg-primary);
            border-color: var(--border-color);
            min-height: calc(100vh - 56px);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                z-index: 1050;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                width: 280px !important;
                height: 100vh;
                top: 56px;
                left: 0;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .stat-card h4 {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1rem;
            }
            
            .stat-card .rounded-circle {
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .stat-card .fa-2x {
                font-size: 1.5em;
            }
            
            .quick-action-btn {
                min-height: 80px;
            }
        }
        
        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .p-4 {
                padding: 1rem !important;
            }
            
            .stat-card .d-flex {
                flex-direction: column;
                text-align: center;
            }
            
            .stat-card .rounded-circle {
                margin: 0 auto 1rem auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link text-white me-3 d-lg-none" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-building me-2"></i>
                TTS PMS
            </a>
            
            <div class="navbar-nav ms-auto align-items-center">
                <!-- Theme Toggle -->
                <div class="nav-item me-3">
                    <span class="theme-toggle text-white" onclick="toggleTheme()">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </span>
                </div>
                
                <!-- Notifications -->
                <div class="nav-item dropdown me-3">
                    <a class="nav-link text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge rounded-pill badge-notification bg-danger">3</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">New payroll ready</a></li>
                        <li><a class="dropdown-item" href="#">Leave request pending</a></li>
                        <li><a class="dropdown-item" href="#">System maintenance</a></li>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="nav-item dropdown">
                    <a class="nav-link text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        Admin User
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="d-flex" style="margin-top: 56px;">
        <!-- Sidebar -->
        <div class="sidebar shadow-sm" id="sidebar" style="width: 240px;">
            <div class="p-3">
                <h6 class="text-muted text-uppercase fw-bold mb-3">Navigation</h6>
                
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Dashboard
                    </a>
                    <a href="employees.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-3"></i>
                        Employees
                    </a>
                    <a href="attendance.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-3"></i>
                        Attendance
                    </a>
                    <a href="payroll.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-money-bill-wave me-3"></i>
                        Payroll
                    </a>
                    <a href="payments.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-credit-card me-3"></i>
                        Payments
                    </a>
                    <a href="invoices.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-invoice me-3"></i>
                        Invoices
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-3"></i>
                        Reports
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-3"></i>
                        Settings
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-grow-1 p-4 main-content">
            <div class="fade-in">
                <!-- Header -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                    <div class="mb-3 mb-md-0">
                        <h2 class="mb-1">Dashboard</h2>
                        <p class="text-muted mb-0">Welcome back! Here's what's happening today.</p>
                    </div>
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-download me-2"></i>
                        <span class="d-none d-sm-inline">Export Report</span>
                        <span class="d-sm-none">Export</span>
                    </button>
                </div>

                <!-- Database Status Alert -->
                <?php if (!$dbConnected): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Database Connection Error:</strong> <?php echo htmlspecialchars($dbError); ?>
                    <br><small>Please check your database configuration or visit the <a href="simple-db-test.php" class="alert-link">database test page</a> for more details.</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php else: ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Database Connected:</strong> System is ready and operational.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                        <div class="card stat-card primary h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle p-3 me-3" style="background-color: rgba(18, 102, 241, 0.1);">
                                    <i class="fas fa-users text-primary fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Total Employees</h6>
                                    <h4 class="mb-0">156</h4>
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up me-1"></i>5.2%
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card stat-card success h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle p-3 me-3" style="background-color: rgba(0, 183, 74, 0.1);">
                                    <i class="fas fa-project-diagram text-success fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Active Projects</h6>
                                    <h4 class="mb-0">23</h4>
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up me-1"></i>12.5%
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card stat-card warning h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle p-3 me-3" style="background-color: rgba(251, 189, 8, 0.1);">
                                    <i class="fas fa-money-bill-wave text-warning fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Pending Payrolls</h6>
                                    <h4 class="mb-0">8</h4>
                                    <small class="text-danger">
                                        <i class="fas fa-arrow-down me-1"></i>2.1%
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card stat-card info h-100">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle p-3 me-3" style="background-color: rgba(57, 192, 237, 0.1);">
                                    <i class="fas fa-chart-line text-info fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Monthly Revenue</h6>
                                    <h4 class="mb-0">$125K</h4>
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up me-1"></i>8.7%
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Row -->
                <div class="row">
                    <!-- Attendance Overview -->
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Attendance Overview
                                </h5>
                                <span class="badge bg-success rounded-pill">94.5% This Month</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Overall Attendance</span>
                                        <span>94.5%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" style="width: 94.5%"></div>
                                    </div>
                                </div>

                                <div class="row text-center">
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <div class="border-end">
                                            <h4 class="text-success mb-1">142</h4>
                                            <small class="text-muted">Present Today</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <div class="border-end">
                                            <h4 class="text-warning mb-1">8</h4>
                                            <small class="text-muted">On Leave</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3 mb-md-0">
                                        <div class="border-end">
                                            <h4 class="text-danger mb-1">6</h4>
                                            <small class="text-muted">Absent</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <h4 class="text-info mb-1">12</h4>
                                        <small class="text-muted">Late Arrivals</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Recent Activities
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex align-items-start">
                                        <div class="rounded-circle p-2 me-3" style="background-color: rgba(0, 183, 74, 0.1);">
                                            <i class="fas fa-money-bill-wave text-success"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 small">Payroll processed for December 2024</p>
                                            <small class="text-muted">System • 2 hours ago</small>
                                        </div>
                                    </div>
                                    <div class="list-group-item d-flex align-items-start">
                                        <div class="rounded-circle p-2 me-3" style="background-color: rgba(251, 189, 8, 0.1);">
                                            <i class="fas fa-calendar-alt text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 small">Leave request approved for John Doe</p>
                                            <small class="text-muted">HR Manager • 4 hours ago</small>
                                        </div>
                                    </div>
                                    <div class="list-group-item d-flex align-items-start">
                                        <div class="rounded-circle p-2 me-3" style="background-color: rgba(18, 102, 241, 0.1);">
                                            <i class="fas fa-credit-card text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 small">Payment of $2,500 received</p>
                                            <small class="text-muted">Finance • 6 hours ago</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 text-center">
                                    <button class="btn btn-link btn-sm">View All Activities</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <button class="btn btn-outline-primary w-100 h-100">
                                    <i class="fas fa-plus d-block mb-2"></i>
                                    <small>Add Employee</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <button class="btn btn-outline-success w-100 h-100">
                                    <i class="fas fa-money-bill d-block mb-2"></i>
                                    <small>Process Payroll</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <button class="btn btn-outline-warning w-100 h-100">
                                    <i class="fas fa-calendar-check d-block mb-2"></i>
                                    <small>Approve Leaves</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <button class="btn btn-outline-info w-100 h-100">
                                    <i class="fas fa-file-invoice d-block mb-2"></i>
                                    <small>Generate Report</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <button class="btn btn-outline-secondary w-100 h-100">
                                    <i class="fas fa-cog d-block mb-2"></i>
                                    <small>Settings</small>
                                </button>
                            </div>
                            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-3">
                                <button class="btn btn-outline-dark w-100 h-100">
                                    <i class="fas fa-question-circle d-block mb-2"></i>
                                    <small>Help & Support</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- MDB Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            const currentTheme = html.getAttribute('data-mdb-theme');
            
            if (currentTheme === 'dark') {
                html.setAttribute('data-mdb-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-mdb-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            
            html.setAttribute('data-mdb-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                const sidebar = document.getElementById('sidebar');
                const sidebarToggle = e.target.closest('[onclick="toggleSidebar()"]');
                
                if (!sidebar.contains(e.target) && !sidebarToggle && window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                }
            });
        });
        
        // Add smooth animations to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>
