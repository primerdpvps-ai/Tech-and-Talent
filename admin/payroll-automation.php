<?php
/**
 * TTS PMS - Automated Payroll Processing
 * Handles automated weekly payroll generation and approval workflow
 */

require_once '../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'payroll_manager'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle automation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'run_weekly_automation') {
            // Run weekly payroll automation
            $weekStart = date('Y-m-d', strtotime('monday last week'));
            $weekEnd = date('Y-m-d', strtotime('sunday last week'));
            
            $result = runWeeklyPayrollAutomation($db, $weekStart, $weekEnd);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
        
        if ($action === 'bulk_approve' && $_SESSION['role'] === 'ceo') {
            $payrollIds = $_POST['payroll_ids'] ?? [];
            $approved = 0;
            
            foreach ($payrollIds as $id) {
                $db->update('tts_payroll_weeks', [
                    'status' => 'approved',
                    'approved_by' => $_SESSION['user_id'],
                    'approved_at' => date('Y-m-d H:i:s')
                ], 'id = ? AND status = ?', [$id, 'pending']);
                $approved++;
            }
            
            $message = "Approved {$approved} payroll records";
            $messageType = 'success';
        }
        
        if ($action === 'bulk_process' && in_array($_SESSION['role'], ['admin', 'payroll_manager'])) {
            $payrollIds = $_POST['payroll_ids'] ?? [];
            $processed = 0;
            
            foreach ($payrollIds as $id) {
                $db->update('tts_payroll_weeks', [
                    'status' => 'processing',
                    'processed_by' => $_SESSION['user_id'],
                    'processed_at' => date('Y-m-d H:i:s')
                ], 'id = ? AND status = ?', [$id, 'approved']);
                $processed++;
            }
            
            $message = "Processed {$processed} payroll records";
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = 'Action failed: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get automation status
$automationStatus = getAutomationStatus($db);

// Get pending approvals by role
$pendingApprovals = getPendingApprovals($db, $_SESSION['role']);

function runWeeklyPayrollAutomation($db, $weekStart, $weekEnd) {
    try {
        // Check if payroll already exists for this week
        $existing = $db->count('tts_payroll_weeks', 'week_start = ?', [$weekStart]);
        if ($existing > 0) {
            return ['success' => false, 'message' => 'Payroll already exists for this week'];
        }
        
        // Get all active employees
        $employees = $db->fetchAll(
            'SELECT e.user_id, e.role, e.start_date, um.user_id as display_id
             FROM tts_employment e 
             JOIN tts_users_meta um ON e.user_id = um.user_id
             WHERE e.role IN ("employee", "manager", "ceo")'
        );
        
        $processed = 0;
        $totalAmount = 0;
        
        foreach ($employees as $emp) {
            // Calculate payroll for employee
            $payrollData = calculateEmployeePayroll($db, $emp, $weekStart, $weekEnd);
            
            if ($payrollData) {
                $db->insert('tts_payroll_weeks', $payrollData);
                $processed++;
                $totalAmount += $payrollData['final_amount'];
            }
        }
        
        // Log automation run
        log_message('info', 'Weekly payroll automation completed', [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'employees_processed' => $processed,
            'total_amount' => $totalAmount
        ]);
        
        return [
            'success' => true, 
            'message' => "Processed {$processed} employees. Total: Rs. " . number_format($totalAmount)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function calculateEmployeePayroll($db, $employee, $weekStart, $weekEnd) {
    // Get total billable seconds for the week
    $totalSeconds = $db->fetchOne(
        'SELECT COALESCE(SUM(billable_seconds), 0) as total 
         FROM tts_daily_summaries 
         WHERE user_id = ? AND date BETWEEN ? AND ?',
        [$employee['user_id'], $weekStart, $weekEnd]
    )['total'];
    
    $totalHours = $totalSeconds / 3600;
    $hourlyRate = 125; // Base rate
    $baseAmount = $totalHours * $hourlyRate;
    
    // Calculate streak bonus
    $streakDays = $db->fetchOne(
        'SELECT COUNT(*) as days 
         FROM tts_daily_summaries 
         WHERE user_id = ? AND meets_daily_minimum = 1 
         AND date >= DATE_SUB(?, INTERVAL 28 DAY)',
        [$employee['user_id'], $weekEnd]
    )['days'];
    
    $streakBonus = $streakDays >= 28 ? 500 : 0;
    
    // Calculate deductions
    $deductions = $db->fetchOne(
        'SELECT COALESCE(SUM(amount), 0) as total 
         FROM tts_penalties 
         WHERE user_id = ? AND applied_at IS NULL',
        [$employee['user_id']]
    )['total'];
    
    $finalAmount = $baseAmount + $streakBonus - $deductions;
    
    // Mark penalties as applied
    if ($deductions > 0) {
        $db->query(
            'UPDATE tts_penalties SET applied_at = NOW() WHERE user_id = ? AND applied_at IS NULL',
            [$employee['user_id']]
        );
    }
    
    return [
        'user_id' => $employee['user_id'],
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'hours' => round($totalHours, 2),
        'base_amount' => $baseAmount,
        'streak_bonus' => $streakBonus,
        'deductions' => $deductions,
        'final_amount' => $finalAmount,
        'status' => 'pending'
    ];
}

function getAutomationStatus($db) {
    $currentWeek = date('Y-m-d', strtotime('monday this week'));
    $lastWeek = date('Y-m-d', strtotime('monday last week'));
    
    return [
        'current_week_generated' => $db->count('tts_payroll_weeks', 'week_start = ?', [$currentWeek]) > 0,
        'last_week_generated' => $db->count('tts_payroll_weeks', 'week_start = ?', [$lastWeek]) > 0,
        'pending_count' => $db->count('tts_payroll_weeks', 'status = ?', ['pending']),
        'approved_count' => $db->count('tts_payroll_weeks', 'status = ?', ['approved']),
        'processing_count' => $db->count('tts_payroll_weeks', 'status = ?', ['processing'])
    ];
}

function getPendingApprovals($db, $userRole) {
    $conditions = [];
    
    if ($userRole === 'ceo') {
        $conditions[] = 'status = "pending"';
    } elseif (in_array($userRole, ['admin', 'payroll_manager'])) {
        $conditions[] = 'status = "approved"';
    }
    
    if (empty($conditions)) return [];
    
    return $db->fetchAll(
        'SELECT pw.*, um.user_id as display_user_id 
         FROM tts_payroll_weeks pw 
         JOIN tts_users_meta um ON pw.user_id = um.user_id 
         WHERE ' . implode(' OR ', $conditions) . ' 
         ORDER BY pw.week_start DESC 
         LIMIT 20'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Payroll Automation</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, #1266f1, #39c0ed); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .automation-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 2rem; }
        .status-indicator { width: 20px; height: 20px; border-radius: 50%; display: inline-block; margin-right: 10px; }
        .status-active { background-color: #28a745; }
        .status-pending { background-color: #ffc107; }
        .status-inactive { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Payroll Automation Center</h2>
                    <p class="mb-0">Automated payroll processing and approval workflow</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <span class="badge bg-light text-dark">Role: <?php echo ucfirst($_SESSION['role']); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Automation Status -->
        <div class="card automation-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Automation Status</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <span class="status-indicator <?php echo $automationStatus['last_week_generated'] ? 'status-active' : 'status-inactive'; ?>"></span>
                            <div>
                                <h6 class="mb-1">Last Week</h6>
                                <small class="text-muted"><?php echo $automationStatus['last_week_generated'] ? 'Generated' : 'Not Generated'; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <span class="status-indicator <?php echo $automationStatus['pending_count'] > 0 ? 'status-pending' : 'status-active'; ?>"></span>
                            <div>
                                <h6 class="mb-1">Pending Approval</h6>
                                <small class="text-muted"><?php echo $automationStatus['pending_count']; ?> records</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <span class="status-indicator <?php echo $automationStatus['approved_count'] > 0 ? 'status-pending' : 'status-active'; ?>"></span>
                            <div>
                                <h6 class="mb-1">Approved</h6>
                                <small class="text-muted"><?php echo $automationStatus['approved_count']; ?> records</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <span class="status-indicator <?php echo $automationStatus['processing_count'] > 0 ? 'status-pending' : 'status-active'; ?>"></span>
                            <div>
                                <h6 class="mb-1">Processing</h6>
                                <small class="text-muted"><?php echo $automationStatus['processing_count']; ?> records</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="run_weekly_automation">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-cog me-1"></i>Run Weekly Automation
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">Last run: <?php echo date('M j, Y g:i A'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Approvals -->
        <?php if (!empty($pendingApprovals)): ?>
        <div class="card automation-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php if ($_SESSION['role'] === 'ceo'): ?>
                        Pending CEO Approval
                    <?php else: ?>
                        Ready for Processing
                    <?php endif; ?>
                </h5>
                <span class="badge bg-warning"><?php echo count($pendingApprovals); ?> records</span>
            </div>
            <div class="card-body">
                <form method="POST" id="bulkActionForm">
                    <?php if ($_SESSION['role'] === 'ceo'): ?>
                        <input type="hidden" name="action" value="bulk_approve">
                    <?php else: ?>
                        <input type="hidden" name="action" value="bulk_process">
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll" onchange="toggleAll()">
                                    </th>
                                    <th>Employee</th>
                                    <th>Week Period</th>
                                    <th>Hours</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingApprovals as $record): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="payroll_ids[]" value="<?php echo $record['id']; ?>" class="record-checkbox">
                                    </td>
                                    <td>EMP-<?php echo $record['user_id']; ?></td>
                                    <td>
                                        <?php echo date('M j', strtotime($record['week_start'])); ?> - 
                                        <?php echo date('M j, Y', strtotime($record['week_end'])); ?>
                                    </td>
                                    <td><?php echo number_format($record['hours'], 1); ?>h</td>
                                    <td>Rs. <?php echo number_format($record['final_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-warning"><?php echo ucfirst($record['status']); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-success" id="bulkActionBtn" disabled>
                            <i class="fas fa-check me-1"></i>
                            <?php echo $_SESSION['role'] === 'ceo' ? 'Approve Selected' : 'Process Selected'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-file-pdf fa-3x text-primary mb-3"></i>
                        <h5>Generate Reports</h5>
                        <p class="text-muted">Generate payroll reports and summaries</p>
                        <a href="payroll-reports.php" class="btn btn-outline-primary">View Reports</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-cogs fa-3x text-info mb-3"></i>
                        <h5>Automation Settings</h5>
                        <p class="text-muted">Configure automation rules and schedules</p>
                        <a href="automation-settings.php" class="btn btn-outline-info">Settings</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-history fa-3x text-success mb-3"></i>
                        <h5>Audit Trail</h5>
                        <p class="text-muted">View payroll processing history</p>
                        <a href="payroll-audit.php" class="btn btn-outline-success">View Audit</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.record-checkbox');
            const bulkBtn = document.getElementById('bulkActionBtn');
            
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            bulkBtn.disabled = !selectAll.checked;
        }
        
        document.querySelectorAll('.record-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const checked = document.querySelectorAll('.record-checkbox:checked').length;
                document.getElementById('bulkActionBtn').disabled = checked === 0;
                document.getElementById('selectAll').checked = checked === document.querySelectorAll('.record-checkbox').length;
            });
        });
    </script>
</body>
</html>
