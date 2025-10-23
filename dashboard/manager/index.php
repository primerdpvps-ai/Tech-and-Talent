<?php
/**
 * TTS PMS - Manager Dashboard
 * Dashboard for managers to oversee teams and projects
 */

require_once '../../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$db = Database::getInstance();
$user = ['first_name' => 'Demo', 'last_name' => 'Manager', 'email' => $_SESSION['email']];

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'approve_timesheet':
                $timesheetId = (int)($_POST['timesheet_id'] ?? 0);
                if ($timesheetId) {
                    $db->update('tts_timesheets', 
                        ['status' => 'approved', 'approved_by' => $_SESSION['user_id'], 'approved_at' => date('Y-m-d H:i:s')],
                        'id = ?', [$timesheetId]
                    );
                    $message = 'Timesheet approved successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'approve_leave':
                $leaveId = (int)($_POST['leave_id'] ?? 0);
                if ($leaveId) {
                    $db->update('tts_leave_requests', 
                        ['status' => 'approved', 'approved_by' => $_SESSION['user_id'], 'approved_at' => date('Y-m-d H:i:s')],
                        'id = ?', [$leaveId]
                    );
                    $message = 'Leave request approved successfully!';
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
$teamStats = [
    'total_employees' => 12,
    'active_projects' => 8,
    'pending_timesheets' => 15,
    'pending_leaves' => 3
];

$pendingTimesheets = [
    ['id' => 1, 'employee' => 'John Doe', 'date' => '2025-01-20', 'hours' => 8.0, 'description' => 'Frontend development'],
    ['id' => 2, 'employee' => 'Jane Smith', 'date' => '2025-01-20', 'hours' => 7.5, 'description' => 'Database optimization'],
    ['id' => 3, 'employee' => 'Mike Johnson', 'date' => '2025-01-19', 'hours' => 8.0, 'description' => 'Client meeting and documentation']
];

$pendingLeaves = [
    ['id' => 1, 'employee' => 'Sarah Wilson', 'type' => 'Annual Leave', 'start_date' => '2025-02-01', 'end_date' => '2025-02-05', 'days' => 5],
    ['id' => 2, 'employee' => 'Tom Brown', 'type' => 'Sick Leave', 'start_date' => '2025-01-25', 'end_date' => '2025-01-25', 'days' => 1],
    ['id' => 3, 'employee' => 'Lisa Davis', 'type' => 'Emergency Leave', 'start_date' => '2025-01-28', 'end_date' => '2025-01-29', 'days' => 2]
];

$teamPerformance = [
    ['name' => 'John Doe', 'role' => 'Developer', 'hours_week' => 40, 'efficiency' => 95, 'projects' => 3],
    ['name' => 'Jane Smith', 'role' => 'Developer', 'hours_week' => 38, 'efficiency' => 92, 'projects' => 2],
    ['name' => 'Mike Johnson', 'role' => 'Designer', 'hours_week' => 35, 'efficiency' => 88, 'projects' => 4],
    ['name' => 'Sarah Wilson', 'role' => 'QA Tester', 'hours_week' => 40, 'efficiency' => 96, 'projects' => 2]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #fd7e14, #ffc107); color: white; padding: 40px 0; }
        .stat-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-3px); }
        .approval-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 15px; }
        .performance-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .efficiency-bar { height: 8px; border-radius: 4px; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-3">Manager Dashboard</h1>
                    <p class="lead mb-0">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>! Oversee your team's performance</p>
                </div>
                <div class="col-lg-4 text-lg-end">
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
                        <i class="fas fa-users fa-2x text-primary mb-3"></i>
                        <h3><?php echo $teamStats['total_employees']; ?></h3>
                        <p class="text-muted mb-0">Team Members</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-project-diagram fa-2x text-success mb-3"></i>
                        <h3><?php echo $teamStats['active_projects']; ?></h3>
                        <p class="text-muted mb-0">Active Projects</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                        <h3><?php echo $teamStats['pending_timesheets']; ?></h3>
                        <p class="text-muted mb-0">Pending Timesheets</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-times fa-2x text-info mb-3"></i>
                        <h3><?php echo $teamStats['pending_leaves']; ?></h3>
                        <p class="text-muted mb-0">Pending Leaves</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Approvals -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-tasks me-2"></i>Pending Approvals</h4>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary active" onclick="showTimesheets()">Timesheets</button>
                        <button class="btn btn-outline-primary" onclick="showLeaves()">Leaves</button>
                    </div>
                </div>

                <!-- Pending Timesheets -->
                <div id="timesheets-section">
                    <?php foreach ($pendingTimesheets as $timesheet): ?>
                    <div class="card approval-card priority-medium">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($timesheet['employee']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo date('M d, Y', strtotime($timesheet['date'])); ?> • <?php echo $timesheet['hours']; ?> hours</p>
                                    <small class="text-muted"><?php echo htmlspecialchars($timesheet['description']); ?></small>
                                </div>
                                <span class="badge status-pending">Pending</span>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve_timesheet">
                                    <input type="hidden" name="timesheet_id" value="<?php echo $timesheet['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                </form>
                                <button class="btn btn-danger btn-sm" onclick="rejectTimesheet(<?php echo $timesheet['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="viewDetails(<?php echo $timesheet['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pending Leaves -->
                <div id="leaves-section" style="display: none;">
                    <?php foreach ($pendingLeaves as $leave): ?>
                    <div class="card approval-card priority-high">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($leave['employee']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($leave['type']); ?> • <?php echo $leave['days']; ?> day(s)</p>
                                    <small class="text-muted">
                                        <?php echo date('M d', strtotime($leave['start_date'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                    </small>
                                </div>
                                <span class="badge status-pending">Pending</span>
                            </div>
                            <div class="d-flex gap-2 mt-3">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve_leave">
                                    <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                </form>
                                <button class="btn btn-danger btn-sm" onclick="rejectLeave(<?php echo $leave['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="viewLeaveDetails(<?php echo $leave['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Team Performance -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-chart-line me-2"></i>Team Performance</h4>
                    <button class="btn btn-outline-primary btn-sm" onclick="generateReport()">
                        <i class="fas fa-download me-1"></i>Export Report
                    </button>
                </div>

                <div class="card performance-card">
                    <div class="card-body">
                        <?php foreach ($teamPerformance as $member): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($member['name']); ?></h6>
                                    <small class="text-muted"><?php echo $member['efficiency']; ?>% efficiency</small>
                                </div>
                                <p class="text-muted mb-2 small"><?php echo htmlspecialchars($member['role']); ?> • <?php echo $member['hours_week']; ?>h/week • <?php echo $member['projects']; ?> projects</p>
                                <div class="progress efficiency-bar">
                                    <div class="progress-bar bg-<?php echo $member['efficiency'] >= 95 ? 'success' : ($member['efficiency'] >= 90 ? 'warning' : 'danger'); ?>" 
                                         style="width: <?php echo $member['efficiency']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <button class="btn btn-outline-primary btn-sm w-100" onclick="scheduleTeamMeeting()">
                                    <i class="fas fa-calendar-plus me-1"></i>Schedule Meeting
                                </button>
                            </div>
                            <div class="col-6 mb-2">
                                <button class="btn btn-outline-success btn-sm w-100" onclick="assignProject()">
                                    <i class="fas fa-tasks me-1"></i>Assign Project
                                </button>
                            </div>
                            <div class="col-6 mb-2">
                                <button class="btn btn-outline-warning btn-sm w-100" onclick="reviewPerformance()">
                                    <i class="fas fa-star me-1"></i>Performance Review
                                </button>
                            </div>
                            <div class="col-6 mb-2">
                                <button class="btn btn-outline-info btn-sm w-100" onclick="sendAnnouncement()">
                                    <i class="fas fa-bullhorn me-1"></i>Send Announcement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fas fa-check text-white small"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1">Timesheet Approved</h6>
                                    <p class="text-muted mb-0 small">Approved John Doe's timesheet for Jan 19, 2025 (8 hours)</p>
                                    <small class="text-muted">2 hours ago</small>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fas fa-calendar text-white small"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1">Leave Request Received</h6>
                                    <p class="text-muted mb-0 small">Sarah Wilson requested 5 days annual leave</p>
                                    <small class="text-muted">4 hours ago</small>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="fas fa-project-diagram text-white small"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1">Project Milestone Completed</h6>
                                    <p class="text-muted mb-0 small">E-commerce Website project reached 75% completion</p>
                                    <small class="text-muted">1 day ago</small>
                                </div>
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
        function showTimesheets() {
            document.getElementById('timesheets-section').style.display = 'block';
            document.getElementById('leaves-section').style.display = 'none';
            document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        function showLeaves() {
            document.getElementById('timesheets-section').style.display = 'none';
            document.getElementById('leaves-section').style.display = 'block';
            document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        function rejectTimesheet(id) {
            if (confirm('Are you sure you want to reject this timesheet?')) {
                alert('Timesheet rejection functionality would be implemented here');
            }
        }
        
        function rejectLeave(id) {
            if (confirm('Are you sure you want to reject this leave request?')) {
                alert('Leave rejection functionality would be implemented here');
            }
        }
        
        function viewDetails(id) {
            alert('View timesheet details for ID: ' + id);
        }
        
        function viewLeaveDetails(id) {
            alert('View leave details for ID: ' + id);
        }
        
        function generateReport() {
            alert('Performance report generation would be implemented here');
        }
        
        function scheduleTeamMeeting() {
            alert('Team meeting scheduling would be implemented here');
        }
        
        function assignProject() {
            alert('Project assignment functionality would be implemented here');
        }
        
        function reviewPerformance() {
            alert('Performance review functionality would be implemented here');
        }
        
        function sendAnnouncement() {
            alert('Announcement sending functionality would be implemented here');
        }
    </script>
</body>
</html>
