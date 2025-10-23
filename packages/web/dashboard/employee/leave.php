<?php
/**
 * TTS PMS - Employee Leave Management
 * Leave application and management for employees
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has employee role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
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
            $success = 'Leave application submitted successfully!';
            
        } elseif (isset($_POST['cancel_leave'])) {
            // Cancel leave request
            $leaveId = (int)$_POST['leave_id'];
            $db->update('tts_leave_requests', 
                ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')], 
                'id = ? AND user_id = ? AND status = ?', 
                [$leaveId, $_SESSION['user_id'], 'pending']
            );
            $success = 'Leave request cancelled successfully!';
        }
        
    } catch (Exception $e) {
        $error = 'Operation failed: ' . $e->getMessage();
    }
}

// Load leave data
try {
    $db = Database::getInstance();
    
    // Get user's leave requests
    $leaveRequests = $db->fetchAll("
        SELECT * FROM tts_leave_requests 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ", [$_SESSION['user_id']]);
    
    // Get leave balance
    $currentYear = date('Y');
    $leaveBalance = $db->fetchOne("
        SELECT * FROM tts_leave_balances 
        WHERE user_id = ? AND year = ?
    ", [$_SESSION['user_id'], $currentYear]);
    
    // If no balance record, create default
    if (!$leaveBalance) {
        $defaultBalance = [
            'user_id' => $_SESSION['user_id'],
            'year' => $currentYear,
            'annual_leave' => 21,
            'sick_leave' => 10,
            'casual_leave' => 5,
            'annual_used' => 0,
            'sick_used' => 0,
            'casual_used' => 0
        ];
        $db->insert('tts_leave_balances', $defaultBalance);
        $leaveBalance = $defaultBalance;
    }
    
} catch (Exception $e) {
    $leaveRequests = [];
    $leaveBalance = [
        'annual_leave' => 21, 'sick_leave' => 10, 'casual_leave' => 5,
        'annual_used' => 0, 'sick_used' => 0, 'casual_used' => 0
    ];
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
        
        .leave-balance-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .leave-type-card {
            transition: transform 0.2s;
        }
        
        .leave-type-card:hover {
            transform: translateY(-2px);
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
                    <a class="nav-link" href="timesheet.php">
                        <i class="fas fa-clock me-2"></i>Timesheet
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
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
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
                            <i class="fas fa-calendar-check me-2"></i>Leave Balance (<?php echo date('Y'); ?>)
                        </h5>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="leave-type-card bg-white bg-opacity-10 p-3 rounded">
                                    <h3><?php echo $leaveBalance['annual_leave'] - $leaveBalance['annual_used']; ?></h3>
                                    <p class="mb-1">Annual Leave</p>
                                    <small class="opacity-75">Used: <?php echo $leaveBalance['annual_used']; ?> / <?php echo $leaveBalance['annual_leave']; ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="leave-type-card bg-white bg-opacity-10 p-3 rounded">
                                    <h3><?php echo $leaveBalance['sick_leave'] - $leaveBalance['sick_used']; ?></h3>
                                    <p class="mb-1">Sick Leave</p>
                                    <small class="opacity-75">Used: <?php echo $leaveBalance['sick_used']; ?> / <?php echo $leaveBalance['sick_leave']; ?></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="leave-type-card bg-white bg-opacity-10 p-3 rounded">
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

        <!-- Leave Requests -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>My Leave Requests</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leaveRequests)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5>No Leave Requests</h5>
                                <p class="text-muted">You haven't applied for any leave yet.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">
                                    <i class="fas fa-plus me-2"></i>Apply for Leave
                                </button>
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
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaveRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['leave_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($request['start_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($request['end_date'])); ?></td>
                                            <td><?php echo $request['total_days']; ?></td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 150px;" 
                                                      title="<?php echo htmlspecialchars($request['reason']); ?>">
                                                    <?php echo htmlspecialchars($request['reason']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-<?php echo $request['status']; ?>">
                                                    <i class="fas fa-<?php 
                                                        echo match($request['status']) {
                                                            'pending' => 'clock',
                                                            'approved' => 'check-circle',
                                                            'rejected' => 'times-circle',
                                                            'cancelled' => 'ban',
                                                            default => 'question-circle'
                                                        };
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($request['applied_date'])); ?></td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to cancel this leave request?')">
                                                        <input type="hidden" name="cancel_leave" value="1">
                                                        <input type="hidden" name="leave_id" value="<?php echo $request['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
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
                                        <option value="maternity">Maternity Leave</option>
                                        <option value="paternity">Paternity Leave</option>
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
                            <strong>Note:</strong> Leave requests require manager approval. You will be notified via email once your request is processed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calculate total days when dates change
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
    </script>
</body>
</html>
