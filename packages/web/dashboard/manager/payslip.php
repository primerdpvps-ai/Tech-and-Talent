<?php
/**
 * TTS PMS - Manager Payslip
 * Payslip generation for managers
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has manager role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'Payslip';
$currentPage = 'payslip';

// Get payslip data
try {
    $db = Database::getInstance();
    
    // Get user details
    $user = $db->fetchOne("SELECT * FROM tts_users WHERE id = ?", [$_SESSION['user_id']]);
    
    // Get current month payslip or create demo data for managers
    $selectedMonth = $_GET['month'] ?? date('Y-m');
    $payslip = [
        'id' => 'MGR-' . $_SESSION['user_id'],
        'user_id' => $_SESSION['user_id'],
        'pay_period' => $selectedMonth,
        'basic_salary' => 80000.00, // Manager salary
        'allowances' => 20000.00,
        'management_allowance' => 15000.00, // Management bonus
        'overtime_hours' => 0, // Managers typically don't get overtime
        'overtime_rate' => 0,
        'overtime_amount' => 0,
        'gross_salary' => 115000.00,
        'tax_deduction' => 8625.00, // Higher tax bracket
        'insurance_deduction' => 1500.00,
        'other_deductions' => 1000.00,
        'total_deductions' => 11125.00,
        'net_salary' => 103875.00,
        'working_days' => 22,
        'present_days' => 22,
        'absent_days' => 0,
        'leave_days' => 0,
        'status' => 'paid',
        'payment_date' => date('Y-m-d'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
} catch (Exception $e) {
    $user = [];
    $payslip = null;
}

// Format currency
function formatCurrency($amount) {
    return 'PKR ' . number_format($amount, 2);
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
        
        .payslip-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payslip-header {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payslip-body {
            padding: 2rem;
        }
        
        .company-logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .payslip-table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
        }
        
        .payslip-table td {
            border: 1px solid #dee2e6;
            padding: 0.75rem;
        }
        
        .total-row {
            background-color: #f3e5f5;
            font-weight: bold;
        }
        
        .net-salary-row {
            background-color: #e1bee7;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        @media print {
            .sidebar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar no-print">
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
                    <a class="nav-link active" href="payslip.php">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Payslip
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="leave.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2><i class="fas fa-file-invoice-dollar me-2"></i>Manager Payslip</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
                <button class="btn btn-success" onclick="downloadPDF()">
                    <i class="fas fa-download me-2"></i>Download
                </button>
            </div>
        </div>

        <?php if ($payslip): ?>
        <div class="payslip-container">
            <!-- Payslip Header -->
            <div class="payslip-header">
                <div class="company-logo">
                    <i class="fas fa-building"></i>
                </div>
                <h2 class="mb-1">Tech & Talent Solutions</h2>
                <p class="mb-0">Precision Data, Global Talent</p>
                <div class="mt-3">
                    <h4>MANAGER PAYSLIP</h4>
                    <p class="mb-0">Pay Period: <?php echo date('F Y', strtotime($payslip['pay_period'] . '-01')); ?></p>
                </div>
            </div>

            <!-- Payslip Body -->
            <div class="payslip-body">
                <!-- Employee Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">Manager Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Manager ID:</strong></td>
                                <td>MGR-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Department:</strong></td>
                                <td>Operations Management</td>
                            </tr>
                            <tr>
                                <td><strong>Designation:</strong></td>
                                <td>Operations Manager</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary mb-3">Payslip Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Payslip ID:</strong></td>
                                <td><?php echo $payslip['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Pay Period:</strong></td>
                                <td><?php echo date('F Y', strtotime($payslip['pay_period'] . '-01')); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Date:</strong></td>
                                <td><?php echo date('d M Y', strtotime($payslip['payment_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-success">Paid</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">Attendance Summary</h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h4 class="text-primary"><?php echo $payslip['working_days']; ?></h4>
                                        <small class="text-muted">Working Days</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h4 class="text-success"><?php echo $payslip['present_days']; ?></h4>
                                        <small class="text-muted">Present Days</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h4 class="text-danger"><?php echo $payslip['absent_days']; ?></h4>
                                        <small class="text-muted">Absent Days</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <h4 class="text-warning"><?php echo $payslip['leave_days']; ?></h4>
                                        <small class="text-muted">Leave Days</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Salary Breakdown -->
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-success mb-3">Earnings</h5>
                        <table class="table payslip-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Basic Salary</td>
                                    <td class="text-end"><?php echo formatCurrency($payslip['basic_salary']); ?></td>
                                </tr>
                                <tr>
                                    <td>Allowances</td>
                                    <td class="text-end"><?php echo formatCurrency($payslip['allowances']); ?></td>
                                </tr>
                                <tr>
                                    <td>Management Allowance</td>
                                    <td class="text-end"><?php echo formatCurrency($payslip['management_allowance']); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong>Gross Salary</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($payslip['gross_salary']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="text-danger mb-3">Deductions</h5>
                        <table class="table payslip-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Income Tax</td>
                                    <td class="text-end"><?php echo formatCurrency($payslip['tax_deduction']); ?></td>
                                </tr>
                                <tr>
                                    <td>Health Insurance</td>
                                    <td class="text-end"><?php echo formatCurrency($payslip['insurance_deduction']); ?></td>
                                </tr>
                                <tr>
                                    <td>Other Deductions</td>
                                    <td class="text-end"><?php echo formatCurrency($payslip['other_deductions']); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td><strong>Total Deductions</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($payslip['total_deductions']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Net Salary -->
                <div class="row mt-4">
                    <div class="col-12">
                        <table class="table payslip-table">
                            <tr class="net-salary-row">
                                <td class="text-center">
                                    <h4 class="mb-0">NET SALARY: <?php echo formatCurrency($payslip['net_salary']); ?></h4>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Management Benefits -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-user-tie me-2"></i>Management Benefits</h6>
                            <ul class="mb-0">
                                <li>Management allowance included for leadership responsibilities</li>
                                <li>Eligible for performance-based bonuses</li>
                                <li>Enhanced health insurance coverage</li>
                                <li>Flexible working arrangements</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="text-center text-muted">
                            <small>
                                This is a computer-generated payslip and does not require a signature.<br>
                                For any queries, please contact HR at hr@tts-pms.com
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.querySelector('.payslip-container');
            
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                
                let position = 0;
                
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                const filename = `Manager_Payslip_${<?php echo json_encode($selectedMonth); ?>}.pdf`;
                pdf.save(filename);
            });
        }
    </script>
</body>
</html>
