<?php
/**
 * TTS PMS - Payslip Generator
 * Generates PDF payslips for approved payroll records
 */

require_once '../config/init.php';
require_once '../vendor/autoload.php'; // For TCPDF or similar PDF library

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'ceo', 'payroll_manager'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// Handle payslip generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'generate_single') {
            $payrollId = $_POST['payroll_id'] ?? '';
            $result = generatePayslip($db, $payrollId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
        
        if ($action === 'generate_batch') {
            $weekStart = $_POST['week_start'] ?? '';
            $result = generateBatchPayslips($db, $weekStart);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
        
        if ($action === 'download_payslip') {
            $payrollId = $_POST['payroll_id'] ?? '';
            downloadPayslip($db, $payrollId);
            exit;
        }
        
    } catch (Exception $e) {
        $message = 'Action failed: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get payroll records ready for payslip generation
$readyPayrolls = $db->fetchAll(
    'SELECT pw.*, um.user_id as display_user_id, e.role
     FROM tts_payroll_weeks pw 
     JOIN tts_users_meta um ON pw.user_id = um.user_id 
     LEFT JOIN tts_employment e ON pw.user_id = e.user_id
     WHERE pw.status = "approved" AND pw.payslip_generated = FALSE
     ORDER BY pw.week_start DESC, pw.user_id ASC
     LIMIT 50'
);

// Get recent generated payslips
$recentPayslips = $db->fetchAll(
    'SELECT pw.*, um.user_id as display_user_id, e.role
     FROM tts_payroll_weeks pw 
     JOIN tts_users_meta um ON pw.user_id = um.user_id 
     LEFT JOIN tts_employment e ON pw.user_id = e.user_id
     WHERE pw.payslip_generated = TRUE
     ORDER BY pw.updated_at DESC
     LIMIT 20'
);

function generatePayslip($db, $payrollId) {
    try {
        // Get payroll record
        $payroll = $db->fetchOne(
            'SELECT pw.*, um.user_id as display_user_id, e.role, e.start_date
             FROM tts_payroll_weeks pw 
             JOIN tts_users_meta um ON pw.user_id = um.user_id 
             LEFT JOIN tts_employment e ON pw.user_id = e.user_id
             WHERE pw.id = ?',
            [$payrollId]
        );
        
        if (!$payroll) {
            return ['success' => false, 'message' => 'Payroll record not found'];
        }
        
        // Generate PDF payslip
        $pdfContent = createPayslipPDF($payroll);
        
        // Save PDF file
        $filename = "payslip_EMP{$payroll['user_id']}_" . date('Y-m-d', strtotime($payroll['week_start'])) . ".pdf";
        $filepath = "../uploads/payslips/" . $filename;
        
        // Create directory if not exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        file_put_contents($filepath, $pdfContent);
        
        // Update payroll record
        $db->update('tts_payroll_weeks', [
            'payslip_generated' => true,
            'payslip_path' => $filepath,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$payrollId]);
        
        // Log payslip generation
        log_message('info', 'Payslip generated', [
            'payroll_id' => $payrollId,
            'user_id' => $payroll['user_id'],
            'filename' => $filename
        ]);
        
        return ['success' => true, 'message' => 'Payslip generated successfully'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function generateBatchPayslips($db, $weekStart) {
    try {
        $payrolls = $db->fetchAll(
            'SELECT pw.*, um.user_id as display_user_id, e.role, e.start_date
             FROM tts_payroll_weeks pw 
             JOIN tts_users_meta um ON pw.user_id = um.user_id 
             LEFT JOIN tts_employment e ON pw.user_id = e.user_id
             WHERE pw.week_start = ? AND pw.status = "approved" AND pw.payslip_generated = FALSE',
            [$weekStart]
        );
        
        $generated = 0;
        
        foreach ($payrolls as $payroll) {
            $result = generatePayslip($db, $payroll['id']);
            if ($result['success']) {
                $generated++;
            }
        }
        
        return [
            'success' => true, 
            'message' => "Generated {$generated} payslips for week starting {$weekStart}"
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function createPayslipPDF($payroll) {
    // Simple HTML to PDF conversion (you can use libraries like TCPDF, mPDF, or DomPDF)
    $html = generatePayslipHTML($payroll);
    
    // For now, return HTML content (replace with actual PDF generation)
    // You would use a library like:
    // $pdf = new TCPDF();
    // $pdf->AddPage();
    // $pdf->writeHTML($html);
    // return $pdf->Output('', 'S');
    
    return $html; // Temporary - replace with actual PDF content
}

function generatePayslipHTML($payroll) {
    $weekStart = date('M j, Y', strtotime($payroll['week_start']));
    $weekEnd = date('M j, Y', strtotime($payroll['week_end']));
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Payslip - EMP{$payroll['user_id']}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .company-logo { font-size: 24px; font-weight: bold; color: #1266f1; }
            .payslip-title { font-size: 18px; margin: 10px 0; }
            .employee-info, .pay-details { margin: 20px 0; }
            .info-row { display: flex; justify-content: space-between; margin: 5px 0; }
            .pay-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .pay-table th, .pay-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .pay-table th { background-color: #f8f9fa; }
            .total-row { font-weight: bold; background-color: #e9ecef; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='company-logo'>Tech & Talent Solutions Ltd.</div>
            <div class='payslip-title'>PAYSLIP</div>
            <div>Pay Period: {$weekStart} - {$weekEnd}</div>
        </div>
        
        <div class='employee-info'>
            <div class='info-row'>
                <span><strong>Employee ID:</strong> EMP-{$payroll['user_id']}</span>
                <span><strong>Pay Date:</strong> " . date('M j, Y') . "</span>
            </div>
            <div class='info-row'>
                <span><strong>Position:</strong> " . ucfirst($payroll['role']) . "</span>
                <span><strong>Department:</strong> Operations</span>
            </div>
        </div>
        
        <table class='pay-table'>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Hours/Amount</th>
                    <th>Rate</th>
                    <th>Amount (PKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Regular Hours</td>
                    <td>{$payroll['hours']}</td>
                    <td>125.00</td>
                    <td>" . number_format($payroll['base_amount'], 2) . "</td>
                </tr>
                " . ($payroll['streak_bonus'] > 0 ? "
                <tr>
                    <td>Streak Bonus (28+ days)</td>
                    <td>1</td>
                    <td>500.00</td>
                    <td>" . number_format($payroll['streak_bonus'], 2) . "</td>
                </tr>
                " : "") . "
                " . ($payroll['deductions'] > 0 ? "
                <tr>
                    <td>Deductions</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-" . number_format($payroll['deductions'], 2) . "</td>
                </tr>
                " : "") . "
                <tr class='total-row'>
                    <td colspan='3'><strong>NET PAY</strong></td>
                    <td><strong>PKR " . number_format($payroll['final_amount'], 2) . "</strong></td>
                </tr>
            </tbody>
        </table>
        
        <div class='footer'>
            <p>This is a computer-generated payslip and does not require a signature.</p>
            <p>Generated on " . date('M j, Y g:i A') . " | TTS PMS v1.0</p>
        </div>
    </body>
    </html>
    ";
}

function downloadPayslip($db, $payrollId) {
    $payroll = $db->fetchOne(
        'SELECT * FROM tts_payroll_weeks WHERE id = ? AND payslip_generated = TRUE',
        [$payrollId]
    );
    
    if (!$payroll || !file_exists($payroll['payslip_path'])) {
        header('HTTP/1.0 404 Not Found');
        echo 'Payslip not found';
        return;
    }
    
    $filename = basename($payroll['payslip_path']);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($payroll['payslip_path']));
    
    readfile($payroll['payslip_path']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Payslip Generator</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 2rem 0; margin-bottom: 2rem; }
        .generator-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Payslip Generator</h2>
                    <p class="mb-0">Generate and manage employee payslips</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="payroll-automation.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i>Back to Payroll
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
        
        <!-- Batch Generation -->
        <div class="card generator-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Batch Payslip Generation</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="generate_batch">
                    <div class="col-md-6">
                        <label class="form-label">Week Starting Date</label>
                        <input type="date" name="week_start" class="form-control" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-pdf me-1"></i>Generate Batch Payslips
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Ready for Generation -->
        <?php if (!empty($readyPayrolls)): ?>
        <div class="card generator-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ready for Payslip Generation</h5>
                <span class="badge bg-warning"><?php echo count($readyPayrolls); ?> records</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Week Period</th>
                                <th>Hours</th>
                                <th>Final Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($readyPayrolls as $payroll): ?>
                            <tr>
                                <td>EMP-<?php echo $payroll['user_id']; ?></td>
                                <td>
                                    <?php echo date('M j', strtotime($payroll['week_start'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($payroll['week_end'])); ?>
                                </td>
                                <td><?php echo number_format($payroll['hours'], 1); ?>h</td>
                                <td>PKR <?php echo number_format($payroll['final_amount']); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo ucfirst($payroll['status']); ?></span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="generate_single">
                                        <input type="hidden" name="payroll_id" value="<?php echo $payroll['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-file-pdf"></i> Generate
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Payslips -->
        <?php if (!empty($recentPayslips)): ?>
        <div class="card generator-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Recent Generated Payslips</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Week Period</th>
                                <th>Amount</th>
                                <th>Generated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayslips as $payroll): ?>
                            <tr>
                                <td>EMP-<?php echo $payroll['user_id']; ?></td>
                                <td>
                                    <?php echo date('M j', strtotime($payroll['week_start'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($payroll['week_end'])); ?>
                                </td>
                                <td>PKR <?php echo number_format($payroll['final_amount']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($payroll['updated_at'])); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="download_payslip">
                                        <input type="hidden" name="payroll_id" value="<?php echo $payroll['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download"></i> Download
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
