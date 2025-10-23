<?php
/**
 * TTS PMS - Employee Management
 * Admin panel for managing employees
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

// Get database connection
$db = Database::getInstance();

$message = '';
$messageType = '';

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'update_role':
                $newRole = $_POST['new_role'] ?? '';
                if ($newRole && $employeeId) {
                    $db->update('tts_employment', 
                        ['role' => $newRole, 'updated_at' => date('Y-m-d H:i:s')],
                        'user_id = ?',
                        [$employeeId]
                    );
                    $message = 'Employee role updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'update_salary':
                $newSalary = (float)($_POST['new_salary'] ?? 0);
                if ($newSalary > 0 && $employeeId) {
                    $db->update('tts_employment', 
                        ['salary' => $newSalary, 'updated_at' => date('Y-m-d H:i:s')],
                        'user_id = ?',
                        [$employeeId]
                    );
                    $message = 'Employee salary updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'deactivate':
                if ($employeeId) {
                    $db->update('tts_employment', 
                        ['status' => 'inactive', 'end_date' => date('Y-m-d'), 'updated_at' => date('Y-m-d H:i:s')],
                        'user_id = ?',
                        [$employeeId]
                    );
                    $message = 'Employee deactivated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'activate':
                if ($employeeId) {
                    $db->update('tts_employment', 
                        ['status' => 'active', 'end_date' => null, 'updated_at' => date('Y-m-d H:i:s')],
                        'user_id = ?',
                        [$employeeId]
                    );
                    $message = 'Employee activated successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get employees with their details
$employees = $db->fetchAll("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        e.role,
        e.salary,
        e.start_date,
        e.end_date,
        e.status,
        um.city,
        um.province,
        um.country,
        um.gmail_verified,
        um.mobile_verified,
        (SELECT COUNT(*) FROM tts_payroll_weeks pw WHERE pw.user_id = u.id) as total_payrolls,
        (SELECT SUM(final_amount) FROM tts_payroll_weeks pw WHERE pw.user_id = u.id AND pw.status = 'completed') as total_earned
    FROM tts_users u
    LEFT JOIN tts_employment e ON u.id = e.user_id
    LEFT JOIN tts_users_meta um ON u.id = um.user_id
    WHERE e.role IN ('employee', 'manager', 'new_employee')
    ORDER BY e.start_date DESC
");

// Get statistics
$stats = [
    'total_employees' => $db->count('tts_employment', "role IN ('employee', 'manager', 'new_employee')"),
    'active_employees' => $db->count('tts_employment', "role IN ('employee', 'manager', 'new_employee') AND status = 'active'"),
    'new_employees' => $db->count('tts_employment', "role = 'new_employee' AND status = 'active'"),
    'managers' => $db->count('tts_employment', "role = 'manager' AND status = 'active'")
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - TTS PMS</title>
    
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
        
        .employee-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .employee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .status-badge {
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .role-badge {
            border-radius: 15px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .role-employee {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .role-manager {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .role-new_employee {
            background-color: #f3e5f5;
            color: #7b1fa2;
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
                <a href="employees.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded">
                    <i class="fas fa-users me-2"></i>Employees
                </a>
                <a href="payroll-automation.php" class="nav-link text-white mb-2">
                    <i class="fas fa-money-bill-wave me-2"></i>Payroll
                </a>
                <a href="leaves.php" class="nav-link text-white mb-2">
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
                <h2 class="mb-1">Employee Management</h2>
                <p class="text-muted mb-0">Manage your workforce and employee details</p>
            </div>
            <button class="btn btn-primary d-md-none" type="button" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
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
                        <i class="fas fa-users fa-2x text-primary mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_employees']; ?></h3>
                        <p class="text-muted mb-0">Total Employees</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-2x text-success mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['active_employees']; ?></h3>
                        <p class="text-muted mb-0">Active Employees</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus fa-2x text-info mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['new_employees']; ?></h3>
                        <p class="text-muted mb-0">New Employees</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie fa-2x text-warning mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['managers']; ?></h3>
                        <p class="text-muted mb-0">Managers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employees List -->
        <div class="row">
            <?php foreach ($employees as $employee): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card employee-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="employee-avatar me-3">
                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($employee['email']); ?></small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-mdb-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                        <i class="fas fa-edit me-2"></i>Edit Details
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewPayroll(<?php echo $employee['id']; ?>)">
                                        <i class="fas fa-money-bill me-2"></i>View Payroll
                                    </a></li>
                                    <?php if ($employee['status'] === 'active'): ?>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deactivateEmployee(<?php echo $employee['id']; ?>)">
                                        <i class="fas fa-user-times me-2"></i>Deactivate
                                    </a></li>
                                    <?php else: ?>
                                    <li><a class="dropdown-item text-success" href="#" onclick="activateEmployee(<?php echo $employee['id']; ?>)">
                                        <i class="fas fa-user-check me-2"></i>Activate
                                    </a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Role</small>
                                <span class="role-badge role-<?php echo $employee['role']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $employee['role'])); ?>
                                </span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Status</small>
                                <span class="status-badge status-<?php echo $employee['status']; ?>">
                                    <?php echo ucfirst($employee['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Salary</small>
                                <strong>PKR <?php echo number_format($employee['salary'] ?? 0); ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Start Date</small>
                                <span><?php echo $employee['start_date'] ? date('M d, Y', strtotime($employee['start_date'])) : 'N/A'; ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Location</small>
                                <span><?php echo htmlspecialchars($employee['city'] . ', ' . $employee['country']); ?></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Verification</small>
                                <div>
                                    <?php if ($employee['gmail_verified']): ?>
                                    <i class="fas fa-envelope text-success" title="Email Verified"></i>
                                    <?php else: ?>
                                    <i class="fas fa-envelope text-muted" title="Email Not Verified"></i>
                                    <?php endif; ?>
                                    
                                    <?php if ($employee['mobile_verified']): ?>
                                    <i class="fas fa-mobile-alt text-success ms-2" title="Mobile Verified"></i>
                                    <?php else: ?>
                                    <i class="fas fa-mobile-alt text-muted ms-2" title="Mobile Not Verified"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Total Payrolls</small>
                                <strong><?php echo $employee['total_payrolls'] ?? 0; ?></strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Total Earned</small>
                                <strong>PKR <?php echo number_format($employee['total_earned'] ?? 0); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="employee_id" id="editEmployeeId">
                        
                        <div class="mb-3">
                            <label for="newRole" class="form-label">Role</label>
                            <select class="form-select" name="new_role" id="newRole" required>
                                <option value="new_employee">New Employee</option>
                                <option value="employee">Employee</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="newSalary" class="form-label">Monthly Salary (PKR)</label>
                            <input type="number" class="form-control" name="new_salary" id="newSalary" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
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
        
        function editEmployee(employeeId) {
            document.getElementById('editEmployeeId').value = employeeId;
            const modal = new mdb.Modal(document.getElementById('editEmployeeModal'));
            modal.show();
        }
        
        function viewPayroll(employeeId) {
            window.location.href = 'payroll-automation.php?employee_id=' + employeeId;
        }
        
        function deactivateEmployee(employeeId) {
            if (confirm('Are you sure you want to deactivate this employee?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="employee_id" value="${employeeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function activateEmployee(employeeId) {
            if (confirm('Are you sure you want to activate this employee?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="activate">
                    <input type="hidden" name="employee_id" value="${employeeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
