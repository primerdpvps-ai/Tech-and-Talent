<?php
/**
 * TTS PMS - Employee Dashboard
 * Dashboard for employees to track work, payroll, and leaves
 */

require_once '../../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$db = Database::getInstance();
$user = ['first_name' => 'Demo', 'last_name' => 'Employee', 'email' => $_SESSION['email']];

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'submit_timesheet':
                $date = $_POST['date'] ?? '';
                $hours = (float)($_POST['hours'] ?? 0);
                $description = $_POST['description'] ?? '';
                
                if ($date && $hours > 0) {
                    $db->insert('tts_timesheets', [
                        'user_id' => $_SESSION['user_id'],
                        'date' => $date,
                        'hours_worked' => $hours,
                        'description' => $description,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = 'Timesheet submitted successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'request_leave':
                $startDate = $_POST['start_date'] ?? '';
                $endDate = $_POST['end_date'] ?? '';
                $leaveType = $_POST['leave_type'] ?? '';
                $reason = $_POST['reason'] ?? '';
                
                if ($startDate && $endDate && $leaveType) {
                    $db->insert('tts_leave_requests', [
                        'user_id' => $_SESSION['user_id'],
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'leave_type' => $leaveType,
                        'reason' => $reason,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = 'Leave request submitted successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Sample data
$currentWeekHours = 32.5;
$monthlyHours = 145.0;
$currentStreak = 15;
$pendingPayroll = 45250;

$recentTimesheets = [
    ['date' => '2025-01-20', 'hours' => 8.0, 'status' => 'approved', 'description' => 'Web development tasks'],
    ['date' => '2025-01-19', 'hours' => 7.5, 'status' => 'pending', 'description' => 'Client project work'],
    ['date' => '2025-01-18', 'hours' => 8.0, 'status' => 'approved', 'description' => 'Database optimization']
];

$leaveRequests = [
    ['id' => 1, 'type' => 'Annual Leave', 'start_date' => '2025-02-01', 'end_date' => '2025-02-03', 'status' => 'approved'],
    ['id' => 2, 'type' => 'Sick Leave', 'start_date' => '2025-01-15', 'end_date' => '2025-01-15', 'status' => 'approved']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #6f42c1, #e83e8c); color: white; padding: 40px 0; }
        .stat-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); }
        .timesheet-card, .leave-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .streak-badge { background: linear-gradient(135deg, #ff6b6b, #feca57); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-3">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
                    <p class="lead mb-0">Track your work progress and manage your schedule</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="streak-badge mb-2">
                        <i class="fas fa-fire me-2"></i><?php echo $currentStreak; ?> Day Streak
                    </div>
                    <br>
                    <a href="../../auth/logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                        <h3><?php echo $currentWeekHours; ?></h3>
                        <p class="text-muted mb-0">This Week Hours</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x text-success mb-3"></i>
                        <h3><?php echo $monthlyHours; ?></h3>
                        <p class="text-muted mb-0">Monthly Hours</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-money-bill-wave fa-2x text-warning mb-3"></i>
                        <h3>PKR <?php echo number_format($pendingPayroll); ?></h3>
                        <p class="text-muted mb-0">Pending Payroll</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-fire fa-2x text-danger mb-3"></i>
                        <h3><?php echo $currentStreak; ?></h3>
                        <p class="text-muted mb-0">Day Streak</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#timesheetModal">
                                <i class="fas fa-plus me-2"></i>Submit Timesheet
                            </button>
                            <button class="btn btn-success" data-mdb-toggle="modal" data-mdb-target="#leaveModal">
                                <i class="fas fa-calendar-plus me-2"></i>Request Leave
                            </button>
                            <button class="btn btn-info" onclick="viewPayroll()">
                                <i class="fas fa-money-check me-2"></i>View Payroll
                            </button>
                            <button class="btn btn-warning" onclick="downloadPayslip()">
                                <i class="fas fa-download me-2"></i>Download Payslip
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Leave Balance -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Leave Balance</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary">18</h4>
                                <small class="text-muted">Annual Leave</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">5</h4>
                                <small class="text-muted">Sick Leave</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Timesheets -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-clock me-2"></i>Recent Timesheets</h4>
                    <button class="btn btn-outline-primary btn-sm" onclick="viewAllTimesheets()">
                        <i class="fas fa-list me-1"></i>View All
                    </button>
                </div>

                <?php foreach ($recentTimesheets as $timesheet): ?>
                <div class="card timesheet-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1"><?php echo date('M d, Y', strtotime($timesheet['date'])); ?></h6>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($timesheet['description']); ?></p>
                                <span class="badge status-<?php echo $timesheet['status']; ?>">
                                    <?php echo ucfirst($timesheet['status']); ?>
                                </span>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0"><?php echo $timesheet['hours']; ?>h</h5>
                                <small class="text-muted">PKR <?php echo number_format($timesheet['hours'] * 125); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Leave Requests -->
                <div class="mt-4">
                    <h4 class="mb-3"><i class="fas fa-calendar-times me-2"></i>Recent Leave Requests</h4>
                    
                    <?php foreach ($leaveRequests as $leave): ?>
                    <div class="card leave-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($leave['type']); ?></h6>
                                    <p class="text-muted mb-0">
                                        <?php echo date('M d', strtotime($leave['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                    </p>
                                </div>
                                <span class="badge status-<?php echo $leave['status']; ?>">
                                    <?php echo ucfirst($leave['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Timesheet Modal -->
    <div class="modal fade" id="timesheetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Timesheet</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="submit_timesheet">
                        
                        <div class="mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="date" required max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hours Worked *</label>
                            <input type="number" class="form-control" name="hours" step="0.5" min="0.5" max="12" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Work Description *</label>
                            <textarea class="form-control" name="description" rows="3" required 
                                      placeholder="Describe what you worked on..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Timesheet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Request Leave Modal -->
    <div class="modal fade" id="leaveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Leave</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="request_leave">
                        
                        <div class="mb-3">
                            <label class="form-label">Leave Type *</label>
                            <select class="form-select" name="leave_type" required>
                                <option value="">Select leave type</option>
                                <option value="annual">Annual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="emergency">Emergency Leave</option>
                                <option value="maternity">Maternity Leave</option>
                                <option value="paternity">Paternity Leave</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date *</label>
                                <input type="date" class="form-control" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date *</label>
                                <input type="date" class="form-control" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" rows="3" 
                                      placeholder="Optional: Provide reason for leave..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    <script>
        function viewPayroll() {
            alert('Payroll details would be displayed here');
        }
        
        function downloadPayslip() {
            alert('Payslip download would be initiated here');
        }
        
        function viewAllTimesheets() {
            alert('All timesheets view would be displayed here');
        }
        
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput) {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
