<?php
/**
 * TTS PMS - Payment Management
 * Admin panel for managing payments and transactions
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

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'update_status':
                $paymentId = (int)($_POST['payment_id'] ?? 0);
                $newStatus = $_POST['new_status'] ?? '';
                if ($paymentId && $newStatus) {
                    $db->update('tts_payments', 
                        ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')],
                        'id = ?', [$paymentId]
                    );
                    $message = 'Payment status updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'refund_payment':
                $paymentId = (int)($_POST['payment_id'] ?? 0);
                $refundAmount = (float)($_POST['refund_amount'] ?? 0);
                if ($paymentId && $refundAmount > 0) {
                    // Process refund (integrate with Stripe refund API)
                    $db->update('tts_payments', 
                        ['status' => 'refunded', 'refund_amount' => $refundAmount, 'updated_at' => date('Y-m-d H:i:s')],
                        'id = ?', [$paymentId]
                    );
                    $message = 'Refund processed successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get payments with gig details
$payments = $db->fetchAll("
    SELECT 
        p.*,
        g.title as gig_title,
        g.description as gig_description,
        u.first_name,
        u.last_name,
        u.email
    FROM tts_payments p
    LEFT JOIN tts_gigs g ON p.gig_id = g.id
    LEFT JOIN tts_users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");

// Get payment statistics
$stats = [
    'total_payments' => $db->count('tts_payments'),
    'successful_payments' => $db->count('tts_payments', "status = 'completed'"),
    'pending_payments' => $db->count('tts_payments', "status = 'pending'"),
    'failed_payments' => $db->count('tts_payments', "status = 'failed'"),
    'total_revenue' => $db->fetchOne("SELECT SUM(amount) as total FROM tts_payments WHERE status = 'completed'")['total'] ?? 0,
    'total_refunds' => $db->fetchOne("SELECT SUM(refund_amount) as total FROM tts_payments WHERE status = 'refunded'")['total'] ?? 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #1266f1, #39c0ed); min-height: 100vh; width: 250px; position: fixed; }
        .main-content { margin-left: 250px; padding: 20px; }
        .payment-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        @media (max-width: 768px) { .sidebar { margin-left: -250px; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4"><i class="fas fa-shield-alt me-2"></i>TTS Admin</h4>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link text-white mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="applications.php" class="nav-link text-white mb-2"><i class="fas fa-file-alt me-2"></i>Applications</a>
                <a href="employees.php" class="nav-link text-white mb-2"><i class="fas fa-users me-2"></i>Employees</a>
                <a href="payroll-automation.php" class="nav-link text-white mb-2"><i class="fas fa-money-bill-wave me-2"></i>Payroll</a>
                <a href="leaves.php" class="nav-link text-white mb-2"><i class="fas fa-calendar-times me-2"></i>Leaves</a>
                <a href="clients.php" class="nav-link text-white mb-2"><i class="fas fa-handshake me-2"></i>Clients</a>
                <a href="proposals.php" class="nav-link text-white mb-2"><i class="fas fa-file-contract me-2"></i>Proposals</a>
                <a href="gigs.php" class="nav-link text-white mb-2"><i class="fas fa-briefcase me-2"></i>Gigs</a>
                <a href="payments.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded"><i class="fas fa-credit-card me-2"></i>Payments</a>
                <a href="reports.php" class="nav-link text-white mb-2"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                <a href="settings.php" class="nav-link text-white mb-2"><i class="fas fa-cog me-2"></i>Settings</a>
                <hr class="text-white">
                <a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </nav>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Payment Management</h2>
                <p class="text-muted mb-0">Monitor and manage all payment transactions</p>
            </div>
            <div>
                <button class="btn btn-primary" onclick="exportPayments()">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                        <h4><?php echo $stats['total_payments']; ?></h4>
                        <small class="text-muted">Total Payments</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4><?php echo $stats['successful_payments']; ?></h4>
                        <small class="text-muted">Successful</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h4><?php echo $stats['pending_payments']; ?></h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h4><?php echo $stats['failed_payments']; ?></h4>
                        <small class="text-muted">Failed</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                        <h4>PKR <?php echo number_format($stats['total_revenue']); ?></h4>
                        <small class="text-muted">Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-undo fa-2x text-secondary mb-2"></i>
                        <h4>PKR <?php echo number_format($stats['total_refunds']); ?></h4>
                        <small class="text-muted">Refunds</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Customer</th>
                                <th>Gig</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><code>#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></code></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($payment['gig_title']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($payment['gig_description'], 0, 50)) . '...'; ?></small>
                                    </div>
                                </td>
                                <td><strong>PKR <?php echo number_format($payment['amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'secondary'
                                    ];
                                    $statusColor = $statusColors[$payment['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusColor; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewPayment(<?php echo $payment['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($payment['status'] === 'completed'): ?>
                                        <button class="btn btn-outline-warning" onclick="refundPayment(<?php echo $payment['id']; ?>, <?php echo $payment['amount']; ?>)">
                                            <i class="fas fa-undo"></i>
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

    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Refund</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="refund_payment">
                        <input type="hidden" name="payment_id" id="refundPaymentId">
                        
                        <div class="mb-3">
                            <label class="form-label">Refund Amount (PKR)</label>
                            <input type="number" class="form-control" name="refund_amount" id="refundAmount" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Refund Reason</label>
                            <textarea class="form-control" name="refund_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Process Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    <script>
        function viewPayment(paymentId) {
            // Implement payment details view
            alert('View payment details for ID: ' + paymentId);
        }
        
        function refundPayment(paymentId, amount) {
            document.getElementById('refundPaymentId').value = paymentId;
            document.getElementById('refundAmount').value = amount;
            document.getElementById('refundAmount').max = amount;
            
            const modal = new mdb.Modal(document.getElementById('refundModal'));
            modal.show();
        }
        
        function exportPayments() {
            // Implement payment export functionality
            alert('Export functionality would be implemented here');
        }
    </script>
</body>
</html>
