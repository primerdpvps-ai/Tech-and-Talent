<?php
/**
 * TTS PMS - Leave Management
 * Admin panel for managing employee leaves
 */

// Load configuration and check admin access
require_once '../config/init.php';

// Start session
session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

try {
    // Get database connection
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Leaves page error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Handle leave actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = $_POST['action'] ?? '';
    $leaveId = (int)($_POST['leave_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'approve':
                if ($leaveId) {
                    $message = "Leave request #$leaveId approved successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'reject':
                $reason = $_POST['rejection_reason'] ?? '';
                if ($leaveId) {
                    $message = "Leave request #$leaveId rejected.";
                    $messageType = 'warning';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error processing leave request: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Demo leave requests data
$leaveRequests = [
    [
        'id' => 1,
        'employee_name' => 'Ahmed Hassan',
        'employee_email' => 'ahmed.hassan@tts.com',
        'leave_type' => 'annual',
        'start_date' => '2025-02-01',
        'end_date' => '2025-02-05',
        'days_count' => 5,
        'reason' => 'Family vacation planned for several months',
        'status' => 'pending',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'remaining_leaves' => 15
    ],
    [
        'id' => 2,
        'employee_name' => 'Sarah Khan',
        'employee_email' => 'sarah.khan@tts.com',
        'leave_type' => 'sick',
        'start_date' => '2025-01-22',
        'end_date' => '2025-01-24',
        'days_count' => 3,
        'reason' => 'Medical appointment and recovery',
        'status' => 'approved',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'approved_at' => date('Y-m-d H:i:s', strtotime('-6 hours')),
        'approved_by' => 'Manager',
        'remaining_leaves' => 12
    ],
    [
        'id' => 3,
        'employee_name' => 'Muhammad Ali',
        'employee_email' => 'muhammad.ali@tts.com',
        'leave_type' => 'emergency',
        'start_date' => '2025-01-20',
        'end_date' => '2025-01-20',
        'days_count' => 1,
        'reason' => 'Family emergency - urgent matter',
        'status' => 'approved',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'approved_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'approved_by' => 'Manager',
        'remaining_leaves' => 18
    ],
    [
        'id' => 4,
        'employee_name' => 'Fatima Sheikh',
        'employee_email' => 'fatima.sheikh@tts.com',
        'leave_type' => 'annual',
        'start_date' => '2025-02-15',
        'end_date' => '2025-02-20',
        'days_count' => 6,
        'reason' => 'Wedding ceremony attendance',
        'status' => 'pending',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'remaining_leaves' => 20
    ]
];

// Filter leave requests based on status
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all') {
    $leaveRequests = array_filter($leaveRequests, function($leave) use ($statusFilter) {
        return $leave['status'] === $statusFilter;
    });
}

// Get statistics
$stats = [
    'total' => 4,
    'pending' => 2,
    'approved' => 2,
    'rejected' => 0
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - TTS PMS</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #6c757d;
            --success-color: #00b74a;
            --danger-color: #f93154;
            --warning-color: #fbbd08;
            --info-color: #39c0ed;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            min-height: 100vh;
            width: 250px;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .leave-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .leave-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .status-badge {
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .leave-type-badge {
            border-radius: 15px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .type-sick {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .type-annual {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        
        .type-emergency {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .type-maternity {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        
        .type-paternity {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.show {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-shield-alt me-2"></i>
                TTS Admin
            </h4>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link text-white mb-2">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="applications.php" class="nav-link text-white mb-2">
                    <i class="fas fa-file-alt me-2"></i>Applications
                </a>
                <a href="employees.php" class="nav-link text-white mb-2">
                    <i class="fas fa-users me-2"></i>Employees
                </a>
                <a href="payroll-automation.php" class="nav-link text-white mb-2">
                    <i class="fas fa-money-bill-wave me-2"></i>Payroll
                </a>
                <a href="leaves.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded">
                    <i class="fas fa-calendar-times me-2"></i>Leaves
                </a>
                <a href="clients.php" class="nav-link text-white mb-2">
                    <i class="fas fa-handshake me-2"></i>Clients
                </a>
                <a href="proposals.php" class="nav-link text-white mb-2">
                    <i class="fas fa-file-contract me-2"></i>Proposals
                </a>
                <a href="gigs.php" class="nav-link text-white mb-2">
                    <i class="fas fa-briefcase me-2"></i>Gigs
                </a>
                <a href="reports.php" class="nav-link text-white mb-2">
                    <i class="fas fa-chart-bar me-2"></i>Reports
                </a>
                <a href="settings.php" class="nav-link text-white mb-2">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
                <hr class="text-white">
                <a href="logout.php" class="nav-link text-white">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Leave Management</h2>
                <p class="text-muted mb-0">Manage employee leave requests and approvals</p>
            </div>
            <div>
                <button class="btn btn-primary me-2" data-mdb-toggle="modal" data-mdb-target="#addLeaveModal">
                    <i class="fas fa-plus me-2"></i>Add Leave
                </button>
                <button class="btn btn-outline-primary d-md-none" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-2x text-primary mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_leaves']; ?></h3>
                        <p class="text-muted mb-0">Total Leaves</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['pending_leaves']; ?></h3>
                        <p class="text-muted mb-0">Pending Approval</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['approved_leaves']; ?></h3>
                        <p class="text-muted mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x text-danger mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['rejected_leaves']; ?></h3>
                        <p class="text-muted mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Requests -->
        <div class="row">
            <?php foreach ($leaves as $leave): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card leave-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($leave['email']); ?></small>
                            </div>
                            <span class="status-badge status-<?php echo $leave['status']; ?>">
                                <?php echo ucfirst($leave['status']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <span class="leave-type-badge type-<?php echo $leave['leave_type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $leave['leave_type'])); ?>
                            </span>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Start Date</small>
                                <span><?php echo date('M d, Y', strtotime($leave['start_date'])); ?></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">End Date</small>
                                <span><?php echo date('M d, Y', strtotime($leave['end_date'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Duration</small>
                            <span><?php 
                                $start = new DateTime($leave['start_date']);
                                $end = new DateTime($leave['end_date']);
                                $diff = $start->diff($end);
                                echo ($diff->days + 1) . ' day(s)';
                            ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Reason</small>
                            <p class="mb-0"><?php echo htmlspecialchars($leave['reason']); ?></p>
                        </div>
                        
                        <?php if ($leave['status'] === 'rejected' && $leave['rejection_reason']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Rejection Reason</small>
                            <p class="mb-0 text-danger"><?php echo htmlspecialchars($leave['rejection_reason']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($leave['approved_by']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Processed By</small>
                            <span><?php echo htmlspecialchars($leave['approver_first_name'] . ' ' . $leave['approver_last_name']); ?></span>
                            <small class="text-muted d-block"><?php echo date('M d, Y H:i', strtotime($leave['approved_at'])); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($leave['status'] === 'pending'): ?>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm flex-fill" onclick="approveLeave(<?php echo $leave['id']; ?>)">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                            <button class="btn btn-danger btn-sm flex-fill" onclick="rejectLeave(<?php echo $leave['id']; ?>)">
                                <i class="fas fa-times me-1"></i>Reject
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Leave Modal -->
    <div class="modal fade" id="addLeaveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Leave</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_leave">
                        
                        <div class="mb-3">
                            <label for="userId" class="form-label">Employee</label>
                            <select class="form-select" name="user_id" id="userId" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['email'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="leaveType" class="form-label">Leave Type</label>
                            <select class="form-select" name="leave_type" id="leaveType" required>
                                <option value="">Select Leave Type</option>
                                <option value="sick">Sick Leave</option>
                                <option value="annual">Annual Leave</option>
                                <option value="emergency">Emergency Leave</option>
                                <option value="maternity">Maternity Leave</option>
                                <option value="paternity">Paternity Leave</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" id="startDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" id="endDate" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" id="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Leave Modal -->
    <div class="modal fade" id="rejectLeaveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="leave_id" id="rejectLeaveId">
                        
                        <div class="mb-3">
                            <label for="rejectionReason" class="form-label">Rejection Reason</label>
                            <textarea class="form-control" name="rejection_reason" id="rejectionReason" rows="3" required 
                                      placeholder="Please provide a reason for rejecting this leave request..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
        
        function approveLeave(leaveId) {
            if (confirm('Are you sure you want to approve this leave request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="leave_id" value="${leaveId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectLeave(leaveId) {
            document.getElementById('rejectLeaveId').value = leaveId;
            const modal = new mdb.Modal(document.getElementById('rejectLeaveModal'));
            modal.show();
        }
        
        // Set minimum date to today for new leaves
        document.getElementById('startDate').min = new Date().toISOString().split('T')[0];
        document.getElementById('endDate').min = new Date().toISOString().split('T')[0];
        
        // Update end date minimum when start date changes
        document.getElementById('startDate').addEventListener('change', function() {
            document.getElementById('endDate').min = this.value;
        });
    </script>
</body>
</html>
