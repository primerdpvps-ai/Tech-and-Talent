<?php
/**
 * TTS PMS - Payroll Management System
 * Complete payroll processing and payslip generation
 */

require_once '../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle payroll actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate_payroll') {
        try {
            $weekStart = $_POST['week_start'];
            $weekEnd = $_POST['week_end'];
            
            // Get all active employees
            $employees = $db->fetchAll(
                'SELECT e.user_id, e.role, e.start_date 
                 FROM tts_employment e 
                 WHERE e.role IN ("employee", "manager", "ceo")'
            );
            
            $hourlyRate = 125; // Base rate from settings
            $streakBonus = 500; // Weekly streak bonus
            
            foreach ($employees as $emp) {
                // Calculate total hours for the week
                $totalSeconds = $db->fetchOne(
                    'SELECT COALESCE(SUM(billable_seconds), 0) as total 
                     FROM tts_daily_summaries 
                     WHERE user_id = ? AND date BETWEEN ? AND ?',
                    [$emp['user_id'], $weekStart, $weekEnd]
                )['total'];
                
                $totalHours = $totalSeconds / 3600;
                $baseAmount = $totalHours * $hourlyRate;
                
                // Check for streak bonus (28+ days)
                $workDays = $db->fetchOne(
                    'SELECT COUNT(*) as days 
                     FROM tts_daily_summaries 
                     WHERE user_id = ? AND meets_daily_minimum = 1 
                     AND date >= DATE_SUB(?, INTERVAL 28 DAY)',
                    [$emp['user_id'], $weekEnd]
                )['days'];
                
                $bonus = $workDays >= 28 ? $streakBonus : 0;
                
                // Calculate deductions
                $deductions = 0;
                $penalties = $db->fetchAll(
                    'SELECT amount FROM tts_penalties 
                     WHERE user_id = ? AND applied_at IS NULL',
                    [$emp['user_id']]
                );
                
                foreach ($penalties as $penalty) {
                    $deductions += $penalty['amount'];
                }
                
                $finalAmount = $baseAmount + $bonus - $deductions;
                
                // Insert payroll record
                $payrollData = [
                    'user_id' => $emp['user_id'],
                    'week_start' => $weekStart,
                    'week_end' => $weekEnd,
                    'hours' => round($totalHours, 2),
                    'base_amount' => $baseAmount,
                    'streak_bonus' => $bonus,
                    'deductions' => $deductions,
                    'final_amount' => $finalAmount,
                    'status' => 'pending'
                ];
                
                $db->insert('tts_payroll_weeks', $payrollData);
                
                // Mark penalties as applied
                $db->query(
                    'UPDATE tts_penalties SET applied_at = NOW() WHERE user_id = ? AND applied_at IS NULL',
                    [$emp['user_id']]
                );
            }
            
            $message = 'Payroll generated successfully for week ' . $weekStart . ' to ' . $weekEnd;
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = 'Payroll generation failed: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
    
    if ($action === 'approve_payroll') {
        $payrollId = $_POST['payroll_id'];
        $db->update('tts_payroll_weeks', ['status' => 'processing'], 'id = ?', [$payrollId]);
        $message = 'Payroll approved for processing';
        $messageType = 'success';
    }
    
    if ($action === 'mark_paid') {
        $payrollId = $_POST['payroll_id'];
        $paymentRef = $_POST['payment_reference'];
        
        $db->update('tts_payroll_weeks', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
            'payment_reference' => $paymentRef
        ], 'id = ?', [$payrollId]);
        
        $message = 'Payment marked as completed';
        $messageType = 'success';
    }
}

// Get current week payroll
$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$currentWeekEnd = date('Y-m-d', strtotime('sunday this week'));

$payrollRecords = $db->fetchAll(
    'SELECT pw.*, um.user_id as display_user_id 
     FROM tts_payroll_weeks pw 
     JOIN tts_users_meta um ON pw.user_id = um.user_id 
     ORDER BY pw.week_start DESC, pw.user_id ASC 
     LIMIT 50'
);

// Get payroll statistics
$stats = [
    'pending_count' => $db->count('tts_payroll_weeks', 'status = ?', ['pending']),
    'processing_count' => $db->count('tts_payroll_weeks', 'status = ?', ['processing']),
    'total_pending_amount' => $db->fetchOne(
        'SELECT COALESCE(SUM(final_amount), 0) as total FROM tts_payroll_weeks WHERE status IN ("pending", "processing")'
    )['total'],
    'this_week_total' => $db->fetchOne(
        'SELECT COALESCE(SUM(final_amount), 0) as total FROM tts_payroll_weeks WHERE week_start = ?',
        [$currentWeekStart]
    )['total']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Payroll Management</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, #1266f1, #39c0ed); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .stat-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        .table-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-1">Payroll Management</h2>
                    <p class="mb-0">Process weekly payroll and generate payslips</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
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
        
        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-warning mb-2"><i class="fas fa-clock fa-2x"></i></div>
                        <h3><?php echo $stats['pending_count']; ?></h3>
                        <p class="text-muted mb-0">Pending Approval</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-info mb-2"><i class="fas fa-cog fa-2x"></i></div>
                        <h3><?php echo $stats['processing_count']; ?></h3>
                        <p class="text-muted mb-0">Processing</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-danger mb-2"><i class="fas fa-money-bill-wave fa-2x"></i></div>
                        <h3>Rs. <?php echo number_format($stats['total_pending_amount']); ?></h3>
                        <p class="text-muted mb-0">Total Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-success mb-2"><i class="fas fa-calendar-week fa-2x"></i></div>
                        <h3>Rs. <?php echo number_format($stats['this_week_total']); ?></h3>
                        <p class="text-muted mb-0">This Week Total</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Generate Payroll -->
        <div class="card table-card mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Generate New Payroll</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="generate_payroll">
                    <div class="col-md-4">
                        <label class="form-label">Week Start</label>
                        <input type="date" name="week_start" class="form-control" value="<?php echo $currentWeekStart; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Week End</label>
                        <input type="date" name="week_end" class="form-control" value="<?php echo $currentWeekEnd; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calculator me-1"></i>Generate Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Payroll Records -->
        <div class="card table-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Payroll Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee ID</th>
                                <th>Week Period</th>
                                <th>Hours</th>
                                <th>Base Amount</th>
                                <th>Bonus</th>
                                <th>Deductions</th>
                                <th>Final Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payrollRecords as $record): ?>
                            <tr>
                                <td>EMP-<?php echo $record['user_id']; ?></td>
                                <td>
                                    <?php echo date('M j', strtotime($record['week_start'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($record['week_end'])); ?>
                                </td>
                                <td><?php echo number_format($record['hours'], 1); ?>h</td>
                                <td>Rs. <?php echo number_format($record['base_amount']); ?></td>
                                <td>Rs. <?php echo number_format($record['streak_bonus']); ?></td>
                                <td>Rs. <?php echo number_format($record['deductions']); ?></td>
                                <td><strong>Rs. <?php echo number_format($record['final_amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'paid' => 'success',
                                        'on_hold' => 'danger'
                                    ][$record['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="payslip.php?id=<?php echo $record['id']; ?>" class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <?php if ($record['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve_payroll">
                                            <input type="hidden" name="payroll_id" value="<?php echo $record['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <?php elseif ($record['status'] === 'processing'): ?>
                                        <button class="btn btn-info btn-sm" onclick="markPaid(<?php echo $record['id']; ?>)">
                                            <i class="fas fa-money-bill"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mark as Paid Modal -->
    <div class="modal fade" id="paidModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Mark as Paid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="mark_paid">
                        <input type="hidden" name="payroll_id" id="paidPayrollId">
                        <div class="mb-3">
                            <label class="form-label">Payment Reference</label>
                            <input type="text" name="payment_reference" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Mark as Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markPaid(payrollId) {
            document.getElementById('paidPayrollId').value = payrollId;
            new bootstrap.Modal(document.getElementById('paidModal')).show();
        }
    </script>
</body>
</html>
