<?php
/**
 * TTS PMS - Employee Timesheet
 * Time tracking and timesheet management for employees
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has employee role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'Timesheet';
$currentPage = 'timesheet';

// Handle timesheet actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        if ($action === 'clock_in') {
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            // Check if already clocked in today
            $existing = $db->fetchOne("SELECT * FROM tts_time_entries WHERE user_id = ? AND date = ?", [$_SESSION['user_id'], $today]);
            
            if ($existing && !$existing['clock_out']) {
                $error = 'You are already clocked in today.';
            } else {
                $data = [
                    'user_id' => $_SESSION['user_id'],
                    'date' => $today,
                    'clock_in' => $currentTime,
                    'status' => 'active'
                ];
                
                if ($existing) {
                    $db->update('tts_time_entries', $data, 'id = ?', [$existing['id']]);
                } else {
                    $db->insert('tts_time_entries', $data);
                }
                
                $success = 'Clocked in successfully at ' . date('g:i A');
            }
        } elseif ($action === 'clock_out') {
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            $entry = $db->fetchOne("SELECT * FROM tts_time_entries WHERE user_id = ? AND date = ? AND clock_out IS NULL", [$_SESSION['user_id'], $today]);
            
            if (!$entry) {
                $error = 'No active clock-in found for today.';
            } else {
                // Calculate total hours
                $clockIn = new DateTime($entry['clock_in']);
                $clockOut = new DateTime($currentTime);
                $diff = $clockOut->diff($clockIn);
                $totalHours = $diff->h + ($diff->i / 60);
                
                // Subtract break time if any
                if ($entry['break_start'] && $entry['break_end']) {
                    $breakStart = new DateTime($entry['break_start']);
                    $breakEnd = new DateTime($entry['break_end']);
                    $breakDiff = $breakEnd->diff($breakStart);
                    $breakHours = $breakDiff->h + ($breakDiff->i / 60);
                    $totalHours -= $breakHours;
                }
                
                $updateData = [
                    'clock_out' => $currentTime,
                    'total_hours' => round($totalHours, 2),
                    'status' => 'completed'
                ];
                
                $db->update('tts_time_entries', $updateData, 'id = ?', [$entry['id']]);
                
                $success = 'Clocked out successfully at ' . date('g:i A') . '. Total hours: ' . round($totalHours, 2);
            }
        } elseif ($action === 'start_break') {
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            $entry = $db->fetchOne("SELECT * FROM tts_time_entries WHERE user_id = ? AND date = ? AND clock_out IS NULL", [$_SESSION['user_id'], $today]);
            
            if (!$entry) {
                $error = 'You must clock in first.';
            } elseif ($entry['break_start'] && !$entry['break_end']) {
                $error = 'You are already on break.';
            } else {
                $db->update('tts_time_entries', ['break_start' => $currentTime, 'status' => 'break'], 'id = ?', [$entry['id']]);
                $success = 'Break started at ' . date('g:i A');
            }
        } elseif ($action === 'end_break') {
            $today = date('Y-m-d');
            $currentTime = date('H:i:s');
            
            $entry = $db->fetchOne("SELECT * FROM tts_time_entries WHERE user_id = ? AND date = ? AND break_start IS NOT NULL AND break_end IS NULL", [$_SESSION['user_id'], $today]);
            
            if (!$entry) {
                $error = 'No active break found.';
            } else {
                $db->update('tts_time_entries', ['break_end' => $currentTime, 'status' => 'active'], 'id = ?', [$entry['id']]);
                $success = 'Break ended at ' . date('g:i A');
            }
        }
        
    } catch (Exception $e) {
        $error = 'Action failed: ' . $e->getMessage();
    }
}

// Load timesheet data
try {
    $db = Database::getInstance();
    
    // Get today's entry
    $today = date('Y-m-d');
    $todayEntry = $db->fetchOne("SELECT * FROM tts_time_entries WHERE user_id = ? AND date = ?", [$_SESSION['user_id'], $today]);
    
    // Get this week's entries
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    
    $weekEntries = $db->fetchAll("
        SELECT * FROM tts_time_entries 
        WHERE user_id = ? AND date BETWEEN ? AND ?
        ORDER BY date DESC
    ", [$_SESSION['user_id'], $weekStart, $weekEnd]);
    
    // Get this month's entries
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    
    $monthEntries = $db->fetchAll("
        SELECT * FROM tts_time_entries 
        WHERE user_id = ? AND date BETWEEN ? AND ?
        ORDER BY date DESC
    ", [$_SESSION['user_id'], $monthStart, $monthEnd]);
    
} catch (Exception $e) {
    $todayEntry = null;
    $weekEntries = [];
    $monthEntries = [];
}

// Calculate totals
$weekTotal = array_sum(array_column($weekEntries, 'total_hours'));
$monthTotal = array_sum(array_column($monthEntries, 'total_hours'));
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
    
    <style>
        .sidebar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
        
        .time-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        
        .time-display {
            font-size: 3rem;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }
        
        .status-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
        }
        
        .action-btn {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 10px;
            margin: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-user me-2"></i>Employee
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
                    <a class="nav-link active" href="timesheet.php">
                        <i class="fas fa-clock me-2"></i>Timesheet
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
            <h2><i class="fas fa-clock me-2"></i>Timesheet</h2>
            <div class="text-muted">
                <i class="fas fa-calendar me-1"></i><?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Current Status -->
            <div class="col-md-6">
                <div class="card time-card mb-4">
                    <div class="time-display" id="currentTime"><?php echo date('H:i:s'); ?></div>
                    <div class="mt-3">
                        <?php if ($todayEntry): ?>
                            <?php if ($todayEntry['clock_in'] && !$todayEntry['clock_out']): ?>
                                <?php if ($todayEntry['status'] === 'break'): ?>
                                    <span class="badge status-badge bg-warning">On Break</span>
                                    <div class="mt-2">Started: <?php echo date('g:i A', strtotime($todayEntry['break_start'])); ?></div>
                                <?php else: ?>
                                    <span class="badge status-badge bg-success">Clocked In</span>
                                    <div class="mt-2">Since: <?php echo date('g:i A', strtotime($todayEntry['clock_in'])); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge status-badge bg-secondary">Clocked Out</span>
                                <div class="mt-2">Total: <?php echo $todayEntry['total_hours']; ?> hours</div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge status-badge bg-secondary">Not Clocked In</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Time Summary -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-chart-bar me-2"></i>Time Summary</h5>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border-end">
                                    <h4 class="text-primary"><?php echo $todayEntry ? number_format($todayEntry['total_hours'], 1) : '0.0'; ?></h4>
                                    <small class="text-muted">Today</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border-end">
                                    <h4 class="text-info"><?php echo number_format($weekTotal, 1); ?></h4>
                                    <small class="text-muted">This Week</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <h4 class="text-success"><?php echo number_format($monthTotal, 1); ?></h4>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small>Monthly Progress</small>
                                <small><?php echo min(100, round(($monthTotal / 160) * 100)); ?>%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($monthTotal / 160) * 100); ?>%"></div>
                            </div>
                            <small class="text-muted">Target: 160 hours/month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-4">Time Tracking Actions</h5>
                        
                        <div class="d-flex justify-content-center flex-wrap">
                            <?php if (!$todayEntry || !$todayEntry['clock_in'] || $todayEntry['clock_out']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="clock_in">
                                    <button type="submit" class="btn btn-success action-btn">
                                        <i class="fas fa-play me-2"></i>Clock In
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($todayEntry && $todayEntry['clock_in'] && !$todayEntry['clock_out']): ?>
                                <?php if ($todayEntry['status'] !== 'break'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="start_break">
                                        <button type="submit" class="btn btn-warning action-btn">
                                            <i class="fas fa-pause me-2"></i>Start Break
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="end_break">
                                        <button type="submit" class="btn btn-info action-btn">
                                            <i class="fas fa-play me-2"></i>End Break
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="clock_out">
                                    <button type="submit" class="btn btn-danger action-btn">
                                        <i class="fas fa-stop me-2"></i>Clock Out
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Entries -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Recent Time Entries</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthEntries)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <p>No time entries found for this month</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Clock In</th>
                                            <th>Clock Out</th>
                                            <th>Break</th>
                                            <th>Total Hours</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthEntries as $entry): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($entry['date'])); ?></td>
                                            <td><?php echo $entry['clock_in'] ? date('g:i A', strtotime($entry['clock_in'])) : '-'; ?></td>
                                            <td><?php echo $entry['clock_out'] ? date('g:i A', strtotime($entry['clock_out'])) : '-'; ?></td>
                                            <td>
                                                <?php if ($entry['break_start'] && $entry['break_end']): ?>
                                                    <?php 
                                                    $breakStart = new DateTime($entry['break_start']);
                                                    $breakEnd = new DateTime($entry['break_end']);
                                                    $breakDiff = $breakEnd->diff($breakStart);
                                                    echo $breakDiff->format('%H:%I');
                                                    ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo number_format($entry['total_hours'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($entry['status']) {
                                                        'active' => 'success',
                                                        'break' => 'warning',
                                                        'completed' => 'primary',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($entry['status']); ?>
                                                </span>
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
        // Update current time every second
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            document.getElementById('currentTime').textContent = timeString;
        }
        
        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Auto-refresh page every 5 minutes to sync with server
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
