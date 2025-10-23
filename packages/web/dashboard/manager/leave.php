<?php
/**
 * TTS PMS - Manager Leave Management
 * Leave application and team leave approval for managers
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has manager role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'Leave Management';
$currentPage = 'leave';

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        if (isset($_POST['apply_leave'])) {
            // Apply for leave
            $leaveData = [
                'user_id' => $_SESSION['user_id'],
                'leave_type' => $_POST['leave_type'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'reason' => trim($_POST['reason']),
                'status' => 'pending',
                'applied_date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Calculate total days
            $startDate = new DateTime($leaveData['start_date']);
            $endDate = new DateTime($leaveData['end_date']);
            $interval = $startDate->diff($endDate);
            $leaveData['total_days'] = $interval->days + 1;
            
            $db->insert('tts_leave_requests', $leaveData);
            $success = 'Leave application submitted for CEO approval!';
            
        } elseif (isset($_POST['approve_leave'])) {
            // Approve team member leave
            $leaveId = (int)$_POST['leave_id'];
            $db->update('tts_leave_requests', 
                ['status' => 'approved', 'approved_by' => $_SESSION['user_id'], 'updated_at' => date('Y-m-d H:i:s')], 
                'id = ? AND status = ?', 
                [$leaveId, 'pending']
            );
            $success = 'Leave request approved successfully!';
            
        } elseif (isset($_POST['reject_leave'])) {
            // Reject team member leave
            $leaveId = (int)$_POST['leave_id'];
            $rejectionReason = trim($_POST['rejection_reason']);
            $db->update('tts_leave_requests', 
                ['status' => 'rejected', 'approved_by' => $_SESSION['user_id'], 'rejection_reason' => $rejectionReason, 'updated_at' => date('Y-m-d H:i:s')], 
                'id = ? AND status = ?', 
                [$leaveId, 'pending']
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
    
    // Get manager's leave requests
    $myLeaveRequests = $db->fetchAll("
        SELECT * FROM tts_leave_requests 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ", [$_SESSION['user_id']]);
    
    // Get team leave requests for approval
    $teamLeaveRequests = $db->fetchAll("
        SELECT lr.*, u.first_name, u.last_name, u.email
        FROM tts_leave_requests lr
        JOIN tts_users u ON lr.user_id = u.id
        WHERE u.role IN ('employee', 'new_employee') AND u.status = 'ACTIVE'
        ORDER BY lr.created_at DESC
    ");
    
    // Manager leave balance (enhanced)
    $leaveBalance = [
        'annual_leave' => 25, 'sick_leave' => 12, 'casual_leave' => 8,
        'annual_used' => 2, 'sick_used' => 1, 'casual_used' => 0
    ];
    
} catch (Exception $e) {
    $myLeaveRequests = [];
    $teamLeaveRequests = [];
}
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
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
                <i class="fas fa-user-tie me-2"></i>Manager
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
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="payslip.php">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Payslip
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
            <h2><i class="fas fa-calendar-times me-2"></i>Leave Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); border: none;">
                <i class="fas fa-plus me-2"></i>Apply for Leave
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

        <!-- Leave Balance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card leave-balance-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-calendar-check me-2"></i>Manager Leave Balance (<?php echo date('Y'); ?>)
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
                                    <h3><?php echo $leaveBalance['casual_leave'] - $leaveBalance['casual_used']; ?></h3>
                                    <p class="mb-1">Casual Leave</p>
                                    <small class="opacity-75">Used: <?php echo $leaveBalance['casual_used']; ?> / <?php echo $leaveBalance['casual_leave']; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for My Leave and Team Approvals -->
        <ul class="nav nav-tabs mb-4" id="leaveTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="my-leave-tab" data-bs-toggle="tab" data-bs-target="#my-leave" type="button" role="tab">
                    <i class="fas fa-user me-2"></i>My Leave Requests
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="team-approvals-tab" data-bs-toggle="tab" data-bs-target="#team-approvals" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Team Approvals
                    <?php 
                    $pendingCount = count(array_filter($teamLeaveRequests, fn($req) => $req['status'] === 'pending'));
                    if ($pendingCount > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $pendingCount; ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="leaveTabsContent">
            <!-- My Leave Requests Tab -->
            <div class="tab-pane fade show active" id="my-leave" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>My Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($myLeaveRequests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Leave Requests</h5>
                                <p class="text-muted">You haven't applied for any leave yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Applied On</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($myLeaveRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['leave_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                                            <td><?php echo $request['total_days']; ?></td>
                                            <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                            <td>
                                                <span class="status-<?php echo $request['status']; ?>">
                                                    <i class="fas fa-<?php 
                                                        echo match($request['status']) {
                                                            'pending' => 'clock',
                                                            'approved' => 'check-circle',
                                                            'rejected' => 'times-circle',
                                                            default => 'question-circle'
                                                        };
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($request['status']); ?>
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

            <!-- Team Approvals Tab -->
            <div class="tab-pane fade" id="team-approvals" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users me-2"></i>Team Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($teamLeaveRequests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No Team Requests</h5>
                                <p class="text-muted">No leave requests from your team members.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Leave Type</th>
                                            <th>Duration</th>
                                            <th>Days</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teamLeaveRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
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
                                            <td><?php echo htmlspecialchars($request['reason']); ?></td>
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
                                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-danger" title="Reject" 
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
                        <i class="fas fa-calendar-plus me-2"></i>Apply for Leave
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="apply_leave" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Leave Type *</label>
                                    <select name="leave_type" class="form-select" required>
                                        <option value="">Select leave type</option>
                                        <option value="annual">Annual Leave</option>
                                        <option value="sick">Sick Leave</option>
                                        <option value="casual">Casual Leave</option>
                                        <option value="emergency">Emergency Leave</option>
                                        <option value="management">Management Leave</option>
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
                            <label class="form-label">Reason *</label>
                            <textarea name="reason" class="form-control" rows="3" 
                                      placeholder="Please provide a reason for your leave request..." required></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Manager leave requests require CEO approval.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); border: none;">
                            <i class="fas fa-paper-plane me-2"></i>Submit Request
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
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="reject_leave" value="1">
                        <input type="hidden" name="leave_id" id="rejectLeaveId">
                        
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason *</label>
                            <textarea name="rejection_reason" class="form-control" rows="3" 
                                      placeholder="Please provide a reason for rejection..." required></textarea>
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
