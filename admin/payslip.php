<?php
/**
 * TTS PMS - Payslip Generation
 * Generate PDF payslips for employees
 */

require_once '../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$payrollId = $_GET['id'] ?? 0;

if (!$payrollId) {
    die('Invalid payroll ID');
}

$db = Database::getInstance();

// Get payroll record with employee details
$payroll = $db->fetchOne(
    'SELECT pw.*, um.user_id as display_user_id, e.role, e.start_date,
            DATEDIFF(pw.week_end, e.start_date) as tenure_days
     FROM tts_payroll_weeks pw 
     JOIN tts_users_meta um ON pw.user_id = um.user_id 
     JOIN tts_employment e ON pw.user_id = e.user_id
     WHERE pw.id = ?',
    [$payrollId]
);

if (!$payroll) {
    die('Payroll record not found');
}

// Get daily breakdown for the week
$dailyBreakdown = $db->fetchAll(
    'SELECT date, billable_seconds, meets_daily_minimum 
     FROM tts_daily_summaries 
     WHERE user_id = ? AND date BETWEEN ? AND ? 
     ORDER BY date',
    [$payroll['user_id'], $payroll['week_start'], $payroll['week_end']]
);

// Get penalties for this period
$penalties = $db->fetchAll(
    'SELECT policy_area, amount, reason 
     FROM tts_penalties 
     WHERE user_id = ? AND applied_at BETWEEN ? AND ?',
    [$payroll['user_id'], $payroll['week_start'], $payroll['week_end']]
);

// Company settings
$companyInfo = [
    'name' => 'Tech & Talent Solutions Ltd.',
    'address' => 'Lahore, Pakistan',
    'phone' => '+92 300 1234567',
    'email' => 'payroll@tts.com.pk',
    'website' => 'www.tts.com.pk'
];

// Calculate additional details
$workingDays = count($dailyBreakdown);
$effectiveHours = 0;
foreach ($dailyBreakdown as $day) {
    $effectiveHours += $day['billable_seconds'] / 3600;
}

$avgHoursPerDay = $workingDays > 0 ? $effectiveHours / $workingDays : 0;
$hourlyRate = 125; // From settings

// Generate payslip number
$payslipNumber = 'TTS-' . date('Y', strtotime($payroll['week_start'])) . '-' . 
                 str_pad($payroll['user_id'], 4, '0', STR_PAD_LEFT) . '-' . 
                 date('W', strtotime($payroll['week_start']));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - <?php echo $payslipNumber; ?></title>
    
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.4;
        }
        
        .payslip-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #1266f1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .payslip-header {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .company-logo {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .payslip-content {
            padding: 30px;
        }
        
        .payslip-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h4 {
            color: #1266f1;
            border-bottom: 2px solid #1266f1;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .info-row:nth-child(even) {
            background-color: #f8f9fa;
            margin: 0 -10px;
            padding: 5px 10px;
        }
        
        .earnings-deductions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        
        .section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .section-header {
            background-color: #f8f9fa;
            padding: 15px;
            font-weight: bold;
            color: #1266f1;
        }
        
        .section-content {
            padding: 15px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px dotted #dee2e6;
        }
        
        .amount-row:last-child {
            border-bottom: none;
            font-weight: bold;
            background-color: #f8f9fa;
            margin: 10px -15px -15px;
            padding: 15px;
        }
        
        .daily-breakdown {
            margin: 30px 0;
        }
        
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .breakdown-table th,
        .breakdown-table td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }
        
        .breakdown-table th {
            background-color: #1266f1;
            color: white;
        }
        
        .breakdown-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .net-pay {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 30px 0;
            border-radius: 8px;
        }
        
        .footer-note {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 40px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 10px;
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1266f1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-paid { background: #d4edda; color: #155724; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Payslip
    </button>
    
    <div class="payslip-container">
        <!-- Header -->
        <div class="payslip-header">
            <div class="company-logo">
                <i class="fas fa-building"></i>
            </div>
            <h1><?php echo $companyInfo['name']; ?></h1>
            <p><?php echo $companyInfo['address']; ?> | <?php echo $companyInfo['phone']; ?> | <?php echo $companyInfo['email']; ?></p>
            <h2 style="margin-top: 20px;">PAYSLIP</h2>
        </div>
        
        <!-- Content -->
        <div class="payslip-content">
            <!-- Payslip Information -->
            <div class="payslip-info">
                <div class="info-section">
                    <h4>Employee Information</h4>
                    <div class="info-row">
                        <span>Employee ID:</span>
                        <strong>EMP-<?php echo str_pad($payroll['user_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Employee Name:</span>
                        <strong>Employee #<?php echo $payroll['user_id']; ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Designation:</span>
                        <strong><?php echo ucwords(str_replace('_', ' ', $payroll['role'])); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Join Date:</span>
                        <strong><?php echo date('M j, Y', strtotime($payroll['start_date'])); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Tenure:</span>
                        <strong><?php echo $payroll['tenure_days']; ?> days</strong>
                    </div>
                </div>
                
                <div class="info-section">
                    <h4>Payslip Details</h4>
                    <div class="info-row">
                        <span>Payslip Number:</span>
                        <strong><?php echo $payslipNumber; ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Pay Period:</span>
                        <strong><?php echo date('M j', strtotime($payroll['week_start'])); ?> - <?php echo date('M j, Y', strtotime($payroll['week_end'])); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Payment Date:</span>
                        <strong><?php echo $payroll['paid_at'] ? date('M j, Y', strtotime($payroll['paid_at'])) : 'Pending'; ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Status:</span>
                        <span class="status-badge status-<?php echo $payroll['status']; ?>">
                            <?php echo ucfirst($payroll['status']); ?>
                        </span>
                    </div>
                    <?php if ($payroll['payment_reference']): ?>
                    <div class="info-row">
                        <span>Payment Ref:</span>
                        <strong><?php echo $payroll['payment_reference']; ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Earnings and Deductions -->
            <div class="earnings-deductions">
                <div class="section">
                    <div class="section-header">EARNINGS</div>
                    <div class="section-content">
                        <div class="amount-row">
                            <span>Basic Salary (<?php echo number_format($payroll['hours'], 1); ?> hours @ Rs. <?php echo $hourlyRate; ?>/hr)</span>
                            <span>Rs. <?php echo number_format($payroll['base_amount'], 2); ?></span>
                        </div>
                        <?php if ($payroll['streak_bonus'] > 0): ?>
                        <div class="amount-row">
                            <span>Streak Bonus (28+ days)</span>
                            <span>Rs. <?php echo number_format($payroll['streak_bonus'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="amount-row">
                            <span>GROSS EARNINGS</span>
                            <span>Rs. <?php echo number_format($payroll['base_amount'] + $payroll['streak_bonus'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-header">DEDUCTIONS</div>
                    <div class="section-content">
                        <?php if (!empty($penalties)): ?>
                            <?php foreach ($penalties as $penalty): ?>
                            <div class="amount-row">
                                <span><?php echo htmlspecialchars($penalty['policy_area']); ?></span>
                                <span>Rs. <?php echo number_format($penalty['amount'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="amount-row">
                                <span>No deductions</span>
                                <span>Rs. 0.00</span>
                            </div>
                        <?php endif; ?>
                        <div class="amount-row">
                            <span>TOTAL DEDUCTIONS</span>
                            <span>Rs. <?php echo number_format($payroll['deductions'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Net Pay -->
            <div class="net-pay">
                NET PAY: Rs. <?php echo number_format($payroll['final_amount'], 2); ?>
            </div>
            
            <!-- Daily Breakdown -->
            <div class="daily-breakdown">
                <h4 style="color: #1266f1; border-bottom: 2px solid #1266f1; padding-bottom: 5px;">Daily Work Breakdown</h4>
                <table class="breakdown-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Hours Worked</th>
                            <th>Minimum Met</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDays = 0;
                        $metMinimumDays = 0;
                        foreach ($dailyBreakdown as $day): 
                            $totalDays++;
                            if ($day['meets_daily_minimum']) $metMinimumDays++;
                            $hoursWorked = $day['billable_seconds'] / 3600;
                        ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                            <td><?php echo date('l', strtotime($day['date'])); ?></td>
                            <td><?php echo number_format($hoursWorked, 1); ?>h</td>
                            <td><?php echo $day['meets_daily_minimum'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <?php if ($day['meets_daily_minimum']): ?>
                                    <span style="color: #28a745;">✓ Complete</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">⚠ Below Minimum</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center;">
                    <div>
                        <strong>Total Working Days:</strong><br>
                        <span style="font-size: 1.2rem; color: #1266f1;"><?php echo $totalDays; ?></span>
                    </div>
                    <div>
                        <strong>Days Meeting Minimum:</strong><br>
                        <span style="font-size: 1.2rem; color: #28a745;"><?php echo $metMinimumDays; ?></span>
                    </div>
                    <div>
                        <strong>Average Hours/Day:</strong><br>
                        <span style="font-size: 1.2rem; color: #17a2b8;"><?php echo number_format($avgHoursPerDay, 1); ?>h</span>
                    </div>
                </div>
            </div>
            
            <!-- Footer Note -->
            <div class="footer-note">
                <h5 style="color: #1266f1; margin-bottom: 10px;">Important Notes:</h5>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>This payslip is computer generated and does not require a signature.</li>
                    <li>Operational hours: 11:00 AM - 2:00 AM PKT (Extended: 2:00 AM - 6:00 AM for senior employees)</li>
                    <li>Minimum daily hours: 6 hours (full-time), 2 hours (part-time)</li>
                    <li>Streak bonus is awarded for maintaining 28+ consecutive working days</li>
                    <li>For any queries regarding this payslip, contact: <?php echo $companyInfo['email']; ?></li>
                </ul>
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div>
                    <div class="signature-line">
                        <strong>Authorized by: Payroll Manager</strong><br>
                        <small>Tech & Talent Solutions Ltd.</small>
                    </div>
                </div>
                <div>
                    <div class="signature-line">
                        <strong>Approved by: CEO</strong><br>
                        <small>Tech & Talent Solutions Ltd.</small>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; color: #6c757d; font-size: 0.8rem;">
                Generated on <?php echo date('M j, Y \a\t g:i A'); ?> | TTS PMS v1.0
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print functionality
        if (window.location.search.includes('print=1')) {
            window.print();
        }
    </script>
</body>
</html>
