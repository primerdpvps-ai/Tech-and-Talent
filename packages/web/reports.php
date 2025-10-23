<?php
require_once '../../config/init.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Reports</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- MDB Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
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
        
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        .report-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem var(--shadow-color);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="dashboard.php">
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
                
                <!-- Back to Dashboard -->
                <div class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="fade-in">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                <div class="mb-3 mb-md-0">
                    <h2 class="mb-1">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Reports & Analytics
                    </h2>
                    <p class="text-muted mb-0">Comprehensive business insights and analytics</p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-select" style="width: 150px;">
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                        <option value="all">All time</option>
                    </select>
                    <button class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>
                        Export All
                    </button>
                </div>
            </div>

            <!-- Quick Report Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card report-card h-100" onclick="generateReport('attendance')">
                        <div class="card-body text-center">
                            <div class="rounded-circle p-3 mx-auto mb-3" style="background-color: rgba(18, 102, 241, 0.1); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-check text-primary fa-2x"></i>
                            </div>
                            <h5>Attendance Report</h5>
                            <p class="text-muted mb-0">Employee attendance analytics</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card report-card h-100" onclick="generateReport('payroll')">
                        <div class="card-body text-center">
                            <div class="rounded-circle p-3 mx-auto mb-3" style="background-color: rgba(0, 183, 74, 0.1); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-money-bill-wave text-success fa-2x"></i>
                            </div>
                            <h5>Payroll Report</h5>
                            <p class="text-muted mb-0">Salary and compensation data</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card report-card h-100" onclick="generateReport('financial')">
                        <div class="card-body text-center">
                            <div class="rounded-circle p-3 mx-auto mb-3" style="background-color: rgba(251, 189, 8, 0.1); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-chart-line text-warning fa-2x"></i>
                            </div>
                            <h5>Financial Report</h5>
                            <p class="text-muted mb-0">Revenue and expense analysis</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card report-card h-100" onclick="generateReport('performance')">
                        <div class="card-body text-center">
                            <div class="rounded-circle p-3 mx-auto mb-3" style="background-color: rgba(57, 192, 237, 0.1); width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trophy text-info fa-2x"></i>
                            </div>
                            <h5>Performance Report</h5>
                            <p class="text-muted mb-0">Employee performance metrics</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <!-- Revenue Chart -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Revenue Trend
                            </h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary active">Monthly</button>
                                <button class="btn btn-outline-primary">Weekly</button>
                                <button class="btn btn-outline-primary">Daily</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Distribution -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Department Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="departmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Overview -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Attendance Overview
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="attendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="fas fa-star me-2"></i>
                                Top Performers
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/40" alt="Avatar" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <div class="fw-bold">John Doe</div>
                                            <small class="text-muted">Senior Developer</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">98.5%</div>
                                        <small class="text-muted">Performance</small>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/40" alt="Avatar" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <div class="fw-bold">Jane Smith</div>
                                            <small class="text-muted">HR Manager</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">96.8%</div>
                                        <small class="text-muted">Performance</small>
                                    </div>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/40" alt="Avatar" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <div class="fw-bold">Mike Johnson</div>
                                            <small class="text-muted">Accountant</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-success">94.2%</div>
                                        <small class="text-muted">Performance</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reports -->
            <div class="card">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Recent Reports
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Report Name</th>
                                    <th>Type</th>
                                    <th>Generated By</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Monthly Payroll Summary</td>
                                    <td><span class="badge bg-success">Payroll</span></td>
                                    <td>System Admin</td>
                                    <td>Oct 18, 2024</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Attendance Analytics</td>
                                    <td><span class="badge bg-primary">Attendance</span></td>
                                    <td>HR Manager</td>
                                    <td>Oct 17, 2024</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Financial Overview Q3</td>
                                    <td><span class="badge bg-warning">Financial</span></td>
                                    <td>Finance Team</td>
                                    <td>Oct 15, 2024</td>
                                    <td><span class="badge bg-warning">Processing</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View" disabled>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Download" disabled>
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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

        // Generate report function
        function generateReport(type) {
            alert(`Generating ${type} report... (Demo functionality)`);
        }

        // Initialize charts
        function initCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, 32000, 40000],
                        borderColor: '#1266f1',
                        backgroundColor: 'rgba(18, 102, 241, 0.1)',
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
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Department Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            new Chart(departmentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['IT', 'HR', 'Finance', 'Marketing', 'Operations'],
                    datasets: [{
                        data: [45, 15, 20, 12, 8],
                        backgroundColor: [
                            '#1266f1',
                            '#00b74a',
                            '#fbbd08',
                            '#f93154',
                            '#39c0ed'
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

            // Attendance Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Present',
                        data: [142, 138, 145, 140, 135, 45, 12],
                        backgroundColor: '#00b74a'
                    }, {
                        label: 'Absent',
                        data: [8, 12, 5, 10, 15, 105, 138],
                        backgroundColor: '#f93154'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Load saved theme and initialize
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            
            html.setAttribute('data-mdb-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

            // Initialize charts
            initCharts();
        });
    </script>
</body>
</html>
