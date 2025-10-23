<?php
/**
 * TTS PMS - CEO Leave Management
 * Executive leave management and company-wide leave oversight
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has CEO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ceo') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'Executive Leave Management';
$currentPage = 'leave';

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        if (isset($_POST['apply_leave'])) {
            // CEO leave application
            $leaveData = [
                'user_id' => $_SESSION['user_id'],
                'leave_type' => $_POST['leave_type'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => trim($_POST['reason']),
                'status' => 'approved', // CEO leave auto-approved
                'approved_by' => $_SESSION['user_id'],
                'applied_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Calculate total days
            $startDate = new DateTime($leaveData['start_date']);
            $endDate = new DateTime($leaveData['end_date']);
            $interval = $startDate->diff($endDate);
            $leaveData['total_days'] = $interval->days + 1;
            
            $db->insert('tts_leave_requests', $leaveData);
            $success = 'Executive leave scheduled successfully!';
            
        } elseif (isset($_POST['approve_leave'])) {
            // Approve any employee leave
            $leaveId = (int)$_POST['leave_id'];
            $db->update('tts_leave_requests', 
                ['status' => 'approved', 'approved_by' => $_SESSION['user_id'], 'updated_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$leaveId]
            );
            $success = 'Leave request approved successfully!';
            
        } elseif (isset($_POST['reject_leave'])) {
            // Reject any employee leave
            $leaveId = (int)$_POST['leave_id'];
            $rejectionReason = trim($_POST['rejection_reason']);
            $db->update('tts_leave_requests', 
                ['status' => 'rejected', 'approved_by' => $_SESSION['user_id'], 'rejection_reason' => $rejectionReason, 'updated_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$leaveId]
            );
            $success = 'Leave request rejected successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'Operation failed: ' . $e->getMessage();
    }
}

// Load leave data
try {
    $db = Database::getInstance();
    
    // Get CEO's leave requests
    $myLeaveRequests = $db->fetchAll("
        SELECT * FROM tts_leave_requests 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ", [$_SESSION['user_id']]);
    
    // Get all company leave requests for oversight
    $allLeaveRequests = $db->fetchAll("
        SELECT lr.*, u.first_name, u.last_name, u.email, u.role
        FROM tts_leave_requests lr
        JOIN tts_users u ON lr.user_id = u.id
        WHERE lr.user_id != ?
        ORDER BY lr.created_at DESC
    ", [$_SESSION['user_id']]);
    
    // CEO leave balance (unlimited for practical purposes)
    $leaveBalance = [
        'annual_leave' => 30, 'sick_leave' => 15, 'executive_leave' => 10,
        'annual_used' => 3, 'sick_used' => 0, 'executive_used' => 1
    ];
    
} catch (Exception $e) {
    $myLeaveRequests = [];
    $allLeaveRequests = [];
}

// Get pending count
$pendingCount = count(array_filter($allLeaveRequests, fn($req) => $req['status'] === 'pending'));
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        
        .leave-balance-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-pending { color: #ffc107; }
        .status-approved { color: #28a745; }
        .status-rejected { color: #dc3545; }
        .status-cancelled { color: #6c757d; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4">
                <i class="fas fa-crown me-2"></i>CEO
            </h4>
            
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-line me-2"></i>Executive Reports
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="payslip.php">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Executive Payslip
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="leave.php">
                        <i class="fas fa-calendar-times me-2"></i>Leave Management
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
            <h2><i class="fas fa-calendar-times me-2"></i>Executive Leave Management</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                <i class="fas fa-plus me-2"></i>Schedule Executive Leave
            </button>
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

        <!-- Executive Leave Balance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card leave-balance-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-crown me-2"></i>Executive Leave Entitlement (<?php echo date('Y'); ?>)
                        </h5>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="bg-white bg-opacity-10 p-3 rounded">
                                    <h3><?php echo $leaveBalance['annual_leave'] - $leaveBalance['annual_used']; ?></h3>
                                    <p class="mb-1">Annual Leave</p>
                                    <small class="opacity-75">Used: <?php echo $leaveBalance['annual_used']; ?> / <?php echo $leaveBalance['annual_leave']; ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-white bg-opacity-10 p-3 rounded">
                                    <h3><?php echo $leaveBalance['sick_leave'] - $leaveBalance['sick_used']; ?></h3>
                                    <p class="mb-1">Sick Leave</p>
                                    <small class="opacity-75">Used: <?php echo $leaveBalance['sick_used']; ?> / <?php echo $leaveBalance['sick_leave']; ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="bg-white bg-opacity-10 p-3 rounded">
                                    <h3><?php echo $leaveBalance['executive_leave'] - $leaveBalance['executive_used']; ?></h3>
                                    <p class="mb-1">Executive Leave</p>
                                    <small class="opacity-75">Used: <?php echo $leaveBalance['executive_used']; ?> / <?php echo $leaveBalance['executive_leave']; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for Executive Leave and Company Oversight -->
        <ul class="nav nav-tabs mb-4" id="leaveTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="executive-leave-tab" data-bs-toggle="tab" data-bs-target="#executive-leave" type="button" role="tab">
                    <i class="fas fa-crown me-2"></i>My Executive Leave
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="company-oversight-tab" data-bs-toggle="tab" data-bs-target="#company-oversight" type="button" role="tab">
                    <i class="fas fa-building me-2"></i>Company-wide Oversight
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="leaveTabsContent">
            <!-- Executive Leave Tab -->
            <div class="tab-pane fade show active" id="executive-leave" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-check me-2"></i>Executive Leave Schedule</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($myLeaveRequests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Scheduled Leave</h5>
                                <p class="text-muted">No executive leave scheduled.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Days</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Scheduled On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($myLeaveRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['leave_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M j', strtotime($request['start_date'])); ?> - 
                                                <?php echo date('M j', strtotime($request['end_date'])); ?>
                                            </td>
                                            <td><?php echo $request['total_days']; ?></td>
                                            <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                            <td>
                                                <span class="status-approved">
                                                    <i class="fas fa-check-circle me-1"></i>Approved
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($request['applied_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Company Oversight Tab -->
            <div class="tab-pane fade" id="company-oversight" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-building me-2"></i>Company-wide Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($allLeaveRequests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No Employee Requests</h5>
                                <p class="text-muted">No leave requests from employees.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Role</th>
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allLeaveRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo match($request['role']) {
                                                        'manager' => 'primary',
                                                        'employee' => 'info',
                                                        'new_employee' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['leave_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M j', strtotime($request['start_date'])); ?> - 
                                                <?php echo date('M j', strtotime($request['end_date'])); ?>
                                            </td>
                                            <td><?php echo $request['total_days']; ?></td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                      title="<?php echo htmlspecialchars($request['reason']); ?>">
                                                    <?php echo htmlspecialchars($request['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-<?php echo $request['status']; ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline me-1">
                                                        <input type="hidden" name="approve_leave" value="1">
                                                        <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="CEO Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-danger" title="CEO Reject" 
                                                            onclick="showRejectModal(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
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

    <!-- Apply Leave Modal -->
    <div class="modal fade" id="applyLeaveModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-crown me-2"></i>Schedule Executive Leave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="apply_leave" value="1">
                        
                        <div class="alert alert-success">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Executive Privilege:</strong> Your leave requests are automatically approved.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Leave Type *</label>
                                    <select name="leave_type" class="form-select" required>
                                        <option value="">Select leave type</option>
                                        <option value="annual">Annual Leave</option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="executive">Executive Leave</option>
                                        <option value="business_travel">Business Travel</option>
                                        <option value="conference">Conference/Training</option>
                                        <option value="personal">Personal Leave</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Days</label>
                                    <input type="number" id="totalDays" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" name="start_date" id="startDate" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date *</label>
                                    <input type="date" name="end_date" id="endDate" class="form-control" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Purpose/Reason *</label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Executive meeting, strategic planning, personal time, etc..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-calendar-check me-2"></i>Schedule Leave
                        </button>
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
                    <h5 class="modal-title">CEO Rejection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="reject_leave" value="1">
                        <input type="hidden" name="leave_id" id="rejectLeaveId">
                        
                        <div class="mb-3">
                            <label class="form-label">Executive Decision Reason *</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" 
                                      placeholder="Business requirements, operational needs, etc..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calculate total days
        function calculateDays() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                document.getElementById('totalDays').value = diffDays;
            } else {
                document.getElementById('totalDays').value = '';
            }
        }
        
        document.getElementById('startDate').addEventListener('change', function() {
            document.getElementById('endDate').min = this.value;
            calculateDays();
        });
        
        document.getElementById('endDate').addEventListener('change', calculateDays);
        
        // Show reject modal
        function showRejectModal(leaveId) {
            document.getElementById('rejectLeaveId').value = leaveId;
            new bootstrap.Modal(document.getElementById('rejectLeaveModal')).show();
        }
    </script>
</body>
</html>
