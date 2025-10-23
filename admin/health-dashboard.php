<?php
/**
 * TTS PMS Phase 6 - Health Dashboard
 * Real-time system health monitoring interface
 */

session_start();
require_once '../config/init.php';
require_once '../includes/admin_helpers.php';
require_once '../includes/observability_helpers.php';

require_admin();
require_capability('manage_system');

$healthDashboard = new HealthDashboard();
$errorAggregator = new ErrorAggregator();

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_metrics':
            echo json_encode($healthDashboard->getHealthMetrics());
            exit;
            
        case 'get_error_patterns':
            echo json_encode($errorAggregator->getErrorPatterns());
            exit;
            
        case 'check_error_spikes':
            echo json_encode($errorAggregator->checkErrorSpikes());
            exit;
    }
}

$pageTitle = 'System Health Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .health-tile {
            transition: all 0.3s ease;
            border-left: 4px solid #dee2e6;
        }
        .health-tile.healthy { border-left-color: #28a745; }
        .health-tile.warning { border-left-color: #ffc107; }
        .health-tile.critical { border-left-color: #dc3545; }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 600;
            line-height: 1;
        }
        
        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-healthy { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-critical { background-color: #dc3545; }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .auto-refresh {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Auto-refresh indicator -->
    <div class="auto-refresh">
        <div class="badge bg-success" id="refresh-indicator">
            <i class="fas fa-sync-alt me-1"></i>
            Auto-refresh: <span id="refresh-countdown">30</span>s
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-heartbeat text-primary me-2"></i>
                    System Health Dashboard
                </h1>
                <p class="text-muted mb-0">Real-time monitoring of TTS PMS system health</p>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary" onclick="refreshMetrics()">
                    <i class="fas fa-sync-alt me-1"></i>
                    Refresh Now
                </button>
            </div>
        </div>

        <!-- Health Overview Tiles -->
        <div class="row mb-4" id="health-tiles">
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card health-tile" id="database-tile">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="metric-label">Database</div>
                                <div class="metric-value" id="db-latency">
                                    <div class="loading-spinner"></div>
                                </div>
                                <small class="text-muted">Response time</small>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-database fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card health-tile" id="queue-tile">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="metric-label">Queue Depth</div>
                                <div class="metric-value" id="queue-depth">
                                    <div class="loading-spinner"></div>
                                </div>
                                <small class="text-muted">Pending jobs</small>
                            </div>
                            <div class="text-info">
                                <i class="fas fa-tasks fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card health-tile" id="backup-tile">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="metric-label">Last Backup</div>
                                <div class="metric-value" id="backup-freshness">
                                    <div class="loading-spinner"></div>
                                </div>
                                <small class="text-muted">Hours ago</small>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-shield-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card health-tile" id="errors-tile">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="metric-label">Error Rate</div>
                                <div class="metric-value" id="error-rate">
                                    <div class="loading-spinner"></div>
                                </div>
                                <small class="text-muted">Last 24 hours</small>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Metrics -->
        <div class="row">
            <!-- Database Health -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <span class="status-indicator" id="db-status-indicator"></span>
                            Database Health
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="database-details">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 mb-0" id="db-connections">-</div>
                                    <small class="text-muted">Connections</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="h4 mb-0" id="db-size">-</div>
                                    <small class="text-muted">DB Size (MB)</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" id="db-connection-usage" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">Connection Usage</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Health -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <span class="status-indicator" id="queue-status-indicator"></span>
                            Sync Queue Health
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row" id="queue-details">
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="h5 mb-0 text-success" id="queue-completed">-</div>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="h5 mb-0 text-warning" id="queue-processing">-</div>
                                    <small class="text-muted">Processing</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <div class="h5 mb-0 text-danger" id="queue-failed">-</div>
                                    <small class="text-muted">Failed</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Failure Rate</small>
                                <small class="text-muted" id="queue-failure-rate">0%</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" id="queue-failure-bar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Patterns -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Error Patterns (24h)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm" id="error-patterns-table">
                                <thead>
                                    <tr>
                                        <th>Error Type</th>
                                        <th>Count</th>
                                        <th>Unique IPs</th>
                                        <th>Last Occurrence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="loading-spinner"></div>
                                            Loading error patterns...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-server me-2"></i>
                            System Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Performance</span>
                                <span class="status-indicator status-healthy"></span>
                            </div>
                            <small class="text-muted">Avg response: <span id="avg-response-time">-</span>ms</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Security</span>
                                <span class="status-indicator" id="security-status"></span>
                            </div>
                            <small class="text-muted">Failed logins: <span id="failed-logins">-</span></small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Cache</span>
                                <span class="status-indicator status-healthy"></span>
                            </div>
                            <small class="text-muted">Size: <span id="cache-size">-</span> MB</small>
                        </div>
                        
                        <div class="mt-3 pt-3 border-top">
                            <button class="btn btn-outline-danger btn-sm w-100" onclick="clearAllCaches()">
                                <i class="fas fa-trash me-1"></i>
                                Clear All Caches
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Alerts -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Recent System Events
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="recent-events">
                            <div class="text-center">
                                <div class="loading-spinner"></div>
                                Loading recent events...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let refreshInterval;
        let countdownInterval;
        let countdownSeconds = 30;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshMetrics();
            startAutoRefresh();
        });

        // Refresh all metrics
        function refreshMetrics() {
            fetch('?action=get_metrics')
                .then(response => response.json())
                .then(data => updateHealthTiles(data))
                .catch(error => console.error('Error fetching metrics:', error));

            fetch('?action=get_error_patterns')
                .then(response => response.json())
                .then(data => updateErrorPatterns(data))
                .catch(error => console.error('Error fetching error patterns:', error));
        }

        // Update health tiles
        function updateHealthTiles(metrics) {
            // Database tile
            if (metrics.database) {
                const db = metrics.database;
                document.getElementById('db-latency').textContent = db.latency_ms + 'ms';
                updateTileStatus('database-tile', db.status);
                document.getElementById('db-status-indicator').className = 'status-indicator status-' + db.status;
                
                if (db.connections) {
                    document.getElementById('db-connections').textContent = db.connections.current + '/' + db.connections.max;
                    document.getElementById('db-connection-usage').style.width = db.connections.usage_percent + '%';
                }
                
                if (db.size_mb) {
                    document.getElementById('db-size').textContent = db.size_mb;
                }
            }

            // Queue tile
            if (metrics.queue) {
                const queue = metrics.queue;
                document.getElementById('queue-depth').textContent = queue.depth;
                updateTileStatus('queue-tile', queue.status);
                document.getElementById('queue-status-indicator').className = 'status-indicator status-' + queue.status;
                
                if (queue.stats) {
                    document.getElementById('queue-completed').textContent = queue.stats.completed;
                    document.getElementById('queue-processing').textContent = queue.stats.processing;
                    document.getElementById('queue-failed').textContent = queue.stats.failed;
                    document.getElementById('queue-failure-rate').textContent = queue.failure_rate_percent + '%';
                    document.getElementById('queue-failure-bar').style.width = queue.failure_rate_percent + '%';
                }
            }

            // Backup tile
            if (metrics.backup && metrics.backup.last_backup) {
                const backup = metrics.backup;
                document.getElementById('backup-freshness').textContent = backup.last_backup.hours_ago + 'h';
                updateTileStatus('backup-tile', backup.status);
            }

            // Errors tile
            if (metrics.errors) {
                const errors = metrics.errors;
                document.getElementById('error-rate').textContent = errors.error_rate_percent + '%';
                updateTileStatus('errors-tile', errors.status);
            }

            // Performance metrics
            if (metrics.performance) {
                const perf = metrics.performance;
                document.getElementById('avg-response-time').textContent = (perf.avg_page_update_seconds * 1000).toFixed(0);
                document.getElementById('cache-size').textContent = perf.cache_size_mb;
            }

            // Security metrics
            if (metrics.security) {
                const security = metrics.security;
                document.getElementById('security-status').className = 'status-indicator status-' + security.status;
                document.getElementById('failed-logins').textContent = security.failed_logins_24h;
            }
        }

        // Update error patterns table
        function updateErrorPatterns(data) {
            const tbody = document.querySelector('#error-patterns-table tbody');
            
            if (data.error_types && data.error_types.length > 0) {
                tbody.innerHTML = data.error_types.map(error => `
                    <tr>
                        <td><code>${error.action_type}</code></td>
                        <td><span class="badge bg-danger">${error.count}</span></td>
                        <td>${error.unique_ips}</td>
                        <td><small>${new Date(error.last_occurrence).toLocaleString()}</small></td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No errors in the last 24 hours</td></tr>';
            }
        }

        // Update tile status
        function updateTileStatus(tileId, status) {
            const tile = document.getElementById(tileId);
            tile.className = 'card health-tile ' + status;
        }

        // Start auto-refresh
        function startAutoRefresh() {
            refreshInterval = setInterval(refreshMetrics, 30000); // 30 seconds
            startCountdown();
        }

        // Start countdown
        function startCountdown() {
            countdownSeconds = 30;
            countdownInterval = setInterval(() => {
                countdownSeconds--;
                document.getElementById('refresh-countdown').textContent = countdownSeconds;
                
                if (countdownSeconds <= 0) {
                    countdownSeconds = 30;
                }
            }, 1000);
        }

        // Clear all caches
        function clearAllCaches() {
            if (confirm('Are you sure you want to clear all system caches?')) {
                // Implementation would go here
                alert('Cache clearing functionality would be implemented here');
            }
        }

        // Stop auto-refresh on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) clearInterval(refreshInterval);
            if (countdownInterval) clearInterval(countdownInterval);
        });
    </script>
</body>
</html>
