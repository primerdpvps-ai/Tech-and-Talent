<?php
require_once '../../config/init.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Attendance</title>
    
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
        
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        .clock-in-btn {
            font-size: 1.2rem;
            padding: 1rem 2rem;
            border-radius: 50px;
        }
        
        .time-display {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
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
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Attendance Management
                    </h2>
                    <p class="text-muted mb-0">Track employee attendance and working hours</p>
                </div>
                <div class="time-display" id="currentTime">
                    --:--:--
                </div>
            </div>

            <!-- Clock In/Out Section -->
            <div class="row mb-4">
                <div class="col-lg-8 mx-auto">
                    <div class="card text-center">
                        <div class="card-body p-5">
                            <h4 class="mb-4">Today's Attendance</h4>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <h6 class="text-muted">Clock In</h6>
                                        <div class="h5 text-success" id="clockInTime">Not clocked in</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded">
                                        <h6 class="text-muted">Clock Out</h6>
                                        <div class="h5 text-danger" id="clockOutTime">Not clocked out</div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-3 justify-content-center">
                                <button class="btn btn-success clock-in-btn" id="clockInBtn" onclick="clockIn()">
                                    <i class="fas fa-clock me-2"></i>
                                    Clock In
                                </button>
                                <button class="btn btn-danger clock-in-btn" id="clockOutBtn" onclick="clockOut()" disabled>
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Clock Out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary -->
            <div class="row mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(0, 183, 74, 0.1);">
                                <i class="fas fa-user-check text-success fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Present Today</h6>
                                <h4 class="mb-0">142</h4>
                                <small class="text-success">91% Attendance</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(249, 49, 84, 0.1);">
                                <i class="fas fa-user-times text-danger fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Absent</h6>
                                <h4 class="mb-0">6</h4>
                                <small class="text-danger">4% Absent</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(251, 189, 8, 0.1);">
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Late Arrivals</h6>
                                <h4 class="mb-0">12</h4>
                                <small class="text-warning">8% Late</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(57, 192, 237, 0.1);">
                                <i class="fas fa-user-clock text-info fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">On Leave</h6>
                                <h4 class="mb-0">8</h4>
                                <small class="text-info">Approved</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="card">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Recent Attendance Records
                    </h5>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control form-control-sm" style="width: 150px;">
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-download me-1"></i>
                            Export
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Oct 18, 2024</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://via.placeholder.com/32" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                            <span>John Doe</span>
                                        </div>
                                    </td>
                                    <td><span class="text-success">09:00 AM</span></td>
                                    <td><span class="text-danger">06:00 PM</span></td>
                                    <td>9h 00m</td>
                                    <td><span class="badge bg-success">Present</span></td>
                                </tr>
                                <tr>
                                    <td>Oct 18, 2024</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://via.placeholder.com/32" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                            <span>Jane Smith</span>
                                        </div>
                                    </td>
                                    <td><span class="text-success">09:15 AM</span></td>
                                    <td><span class="text-danger">05:45 PM</span></td>
                                    <td>8h 30m</td>
                                    <td><span class="badge bg-warning">Late</span></td>
                                </tr>
                                <tr>
                                    <td>Oct 18, 2024</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://via.placeholder.com/32" alt="Avatar" class="rounded-circle me-2" width="32" height="32">
                                            <span>Mike Johnson</span>
                                        </div>
                                    </td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>0h 00m</td>
                                    <td><span class="badge bg-info">On Leave</span></td>
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
        let clockedIn = false;

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

        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('currentTime').textContent = timeString;
        }

        // Clock in functionality
        function clockIn() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            
            document.getElementById('clockInTime').textContent = timeString;
            document.getElementById('clockInBtn').disabled = true;
            document.getElementById('clockOutBtn').disabled = false;
            
            clockedIn = true;
            alert('Clocked in successfully at ' + timeString);
        }

        // Clock out functionality
        function clockOut() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            
            document.getElementById('clockOutTime').textContent = timeString;
            document.getElementById('clockInBtn').disabled = false;
            document.getElementById('clockOutBtn').disabled = true;
            
            clockedIn = false;
            alert('Clocked out successfully at ' + timeString);
        }

        // Load saved theme and initialize
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            
            html.setAttribute('data-mdb-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

            // Update time every second
            updateTime();
            setInterval(updateTime, 1000);
        });
    </script>
</body>
</html>
