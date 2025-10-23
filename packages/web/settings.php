<?php
require_once '../../config/init.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Settings</title>
    
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
        
        .settings-nav {
            border-right: 1px solid var(--border-color);
        }
        
        .settings-nav .nav-link {
            color: var(--text-primary);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .settings-nav .nav-link:hover,
        .settings-nav .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .form-control, .form-select {
            background-color: var(--bg-primary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        .form-control:focus, .form-select:focus {
            background-color: var(--bg-primary);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(18, 102, 241, 0.25);
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
            <div class="mb-4">
                <h2 class="mb-1">
                    <i class="fas fa-cog text-primary me-2"></i>
                    System Settings
                </h2>
                <p class="text-muted mb-0">Configure your TTS PMS system preferences</p>
            </div>

            <div class="row">
                <!-- Settings Navigation -->
                <div class="col-lg-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <nav class="nav flex-column settings-nav">
                                <a class="nav-link active" href="#general" data-bs-toggle="tab">
                                    <i class="fas fa-cogs me-2"></i>
                                    General
                                </a>
                                <a class="nav-link" href="#payments" data-bs-toggle="tab">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Payment Gateways
                                </a>
                                <a class="nav-link" href="#email" data-bs-toggle="tab">
                                    <i class="fas fa-envelope me-2"></i>
                                    Email Settings
                                </a>
                                <a class="nav-link" href="#notifications" data-bs-toggle="tab">
                                    <i class="fas fa-bell me-2"></i>
                                    Notifications
                                </a>
                                <a class="nav-link" href="#security" data-bs-toggle="tab">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Security
                                </a>
                                <a class="nav-link" href="#backup" data-bs-toggle="tab">
                                    <i class="fas fa-database me-2"></i>
                                    Backup
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-lg-9">
                    <div class="tab-content">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cogs me-2"></i>
                                        General Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Application Name</label>
                                                <input type="text" class="form-control" value="TTS PMS">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Default Timezone</label>
                                                <select class="form-select">
                                                    <option value="UTC">UTC</option>
                                                    <option value="America/New_York">Eastern Time</option>
                                                    <option value="America/Chicago">Central Time</option>
                                                    <option value="America/Denver">Mountain Time</option>
                                                    <option value="America/Los_Angeles">Pacific Time</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Default Currency</label>
                                                <select class="form-select">
                                                    <option value="USD">USD - US Dollar</option>
                                                    <option value="EUR">EUR - Euro</option>
                                                    <option value="GBP">GBP - British Pound</option>
                                                    <option value="CAD">CAD - Canadian Dollar</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Default Language</label>
                                                <select class="form-select">
                                                    <option value="en">English</option>
                                                    <option value="es">Spanish</option>
                                                    <option value="fr">French</option>
                                                    <option value="de">German</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Working Hours per Day</label>
                                                <input type="number" class="form-control" value="8" min="1" max="24">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Overtime Multiplier</label>
                                                <input type="number" class="form-control" value="1.5" step="0.1" min="1">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Save Changes
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Gateways -->
                        <div class="tab-pane fade" id="payments">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-credit-card me-2"></i>
                                        Payment Gateway Configuration
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Stripe -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">
                                                <i class="fab fa-stripe me-2"></i>
                                                Stripe
                                            </h6>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="stripeEnabled" checked>
                                                <label class="form-check-label" for="stripeEnabled">Enabled</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Publishable Key</label>
                                                <input type="text" class="form-control" placeholder="pk_test_...">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Secret Key</label>
                                                <input type="password" class="form-control" placeholder="sk_test_...">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PayPal -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">
                                                <i class="fab fa-paypal me-2"></i>
                                                PayPal
                                            </h6>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="paypalEnabled">
                                                <label class="form-check-label" for="paypalEnabled">Enabled</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Client ID</label>
                                                <input type="text" class="form-control" placeholder="Your PayPal Client ID">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Client Secret</label>
                                                <input type="password" class="form-control" placeholder="Your PayPal Client Secret">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Google Pay -->
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">
                                                <i class="fab fa-google-pay me-2"></i>
                                                Google Pay
                                            </h6>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="googlepayEnabled">
                                                <label class="form-check-label" for="googlepayEnabled">Enabled</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Merchant ID</label>
                                                <input type="text" class="form-control" placeholder="Your Google Pay Merchant ID">
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Save Payment Settings
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Email Settings -->
                        <div class="tab-pane fade" id="email">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-envelope me-2"></i>
                                        Email Configuration
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" value="smtp.gmail.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" value="587">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Username</label>
                                                <input type="email" class="form-control" placeholder="your-email@gmail.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Password</label>
                                                <input type="password" class="form-control" placeholder="Your app password">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">From Address</label>
                                                <input type="email" class="form-control" placeholder="noreply@yourcompany.com">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">From Name</label>
                                                <input type="text" class="form-control" value="TTS PMS">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Encryption</label>
                                            <select class="form-select">
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                                <option value="none">None</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Save Email Settings
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Test Email
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="tab-pane fade" id="notifications">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bell me-2"></i>
                                        Notification Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Email Notifications</h6>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="emailPayroll" checked>
                                                <label class="form-check-label" for="emailPayroll">
                                                    Payroll processed
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="emailLeave" checked>
                                                <label class="form-check-label" for="emailLeave">
                                                    Leave requests
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="emailPayment" checked>
                                                <label class="form-check-label" for="emailPayment">
                                                    Payment received
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>System Notifications</h6>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="systemMaintenance">
                                                <label class="form-check-label" for="systemMaintenance">
                                                    System maintenance
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="systemBackup" checked>
                                                <label class="form-check-label" for="systemBackup">
                                                    Backup completed
                                                </label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="systemSecurity" checked>
                                                <label class="form-check-label" for="systemSecurity">
                                                    Security alerts
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-3">
                                        <i class="fas fa-save me-2"></i>
                                        Save Notification Settings
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="tab-pane fade" id="security">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        Security Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Session Timeout (minutes)</label>
                                            <input type="number" class="form-control" value="120" min="5" max="1440">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Max Login Attempts</label>
                                            <input type="number" class="form-control" value="5" min="3" max="10">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Password Min Length</label>
                                            <input type="number" class="form-control" value="8" min="6" max="20">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lockout Duration (minutes)</label>
                                            <input type="number" class="form-control" value="15" min="5" max="60">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="twoFactor">
                                            <label class="form-check-label" for="twoFactor">
                                                Enable Two-Factor Authentication
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Save Security Settings
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Backup -->
                        <div class="tab-pane fade" id="backup">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-database me-2"></i>
                                        Backup Settings
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Backup Frequency</label>
                                            <select class="form-select">
                                                <option value="hourly">Hourly</option>
                                                <option value="daily" selected>Daily</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Retention Days</label>
                                            <input type="number" class="form-control" value="30" min="1" max="365">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="autoBackup" checked>
                                            <label class="form-check-label" for="autoBackup">
                                                Enable Automatic Backups
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Save Backup Settings
                                        </button>
                                        <button type="button" class="btn btn-success">
                                            <i class="fas fa-download me-2"></i>
                                            Create Backup Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
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

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            
            html.setAttribute('data-mdb-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

            // Add form submission handlers
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    alert('Settings saved successfully! (Demo functionality)');
                });
            });
        });
    </script>
</body>
</html>
