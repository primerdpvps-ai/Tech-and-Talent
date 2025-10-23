<?php
/**
 * TTS PMS - Proposals Management
 * Admin panel for managing client proposals
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

try {
    // Get database connection
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Proposals page error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Handle proposal actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = $_POST['action'] ?? '';
    $proposalId = (int)($_POST['proposal_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'update_status':
                $newStatus = $_POST['new_status'] ?? '';
                if ($proposalId && $newStatus) {
                    $message = "Proposal #$proposalId status updated to $newStatus successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'add_proposal':
                $message = 'New proposal added successfully!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error processing proposal: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Demo proposals data
$proposals = [
    [
        'id' => 1,
        'business_name' => 'Tech Innovations Ltd.',
        'contact_person' => 'Ahmed Hassan',
        'email' => 'ahmed@techinnovations.com',
        'phone' => '+92 300 1234567',
        'service_type' => 'Data Analytics & Insights',
        'project_details' => 'Need comprehensive data analytics solution for customer behavior analysis and sales forecasting.',
        'budget_range' => 'PKR 100,000 - 200,000',
        'timeline' => '6-8 weeks',
        'status' => 'pending',
        'priority' => 'high',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
        'id' => 2,
        'business_name' => 'Digital Solutions Inc.',
        'contact_person' => 'Sarah Khan',
        'email' => 'sarah@digitalsolutions.com',
        'phone' => '+92 321 9876543',
        'service_type' => 'Data Entry Services',
        'project_details' => 'Large scale data entry project for customer database migration from legacy system.',
        'budget_range' => 'PKR 50,000 - 100,000',
        'timeline' => '4-6 weeks',
        'status' => 'in_progress',
        'priority' => 'medium',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'approved_at' => date('Y-m-d H:i:s', strtotime('-6 hours'))
    ],
    [
        'id' => 3,
        'business_name' => 'StartUp Hub',
        'contact_person' => 'Muhammad Ali',
        'email' => 'ali@startuphub.pk',
        'phone' => '+92 333 5555555',
        'service_type' => 'Excel & Spreadsheet Solutions',
        'project_details' => 'Advanced Excel dashboard creation with automated reporting and data visualization.',
        'budget_range' => 'PKR 75,000 - 150,000',
        'timeline' => '3-4 weeks',
        'status' => 'completed',
        'priority' => 'medium',
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'completed_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ]
];

// Filter proposals based on status
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all') {
    $proposals = array_filter($proposals, function($proposal) use ($statusFilter) {
        return $proposal['status'] === $statusFilter;
    });
}

// Get statistics
$stats = [
    'total' => 3,
    'pending' => 1,
    'in_progress' => 1,
    'completed' => 1,
    'rejected' => 0
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Management - TTS PMS</title>
    
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
        
        .proposal-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
        }
        
        .proposal-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .priority-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .priority-high {
            background-color: var(--danger-color);
        }
        
        .priority-medium {
            background-color: var(--warning-color);
        }
        
        .priority-low {
            background-color: var(--success-color);
        }
        
        .status-badge {
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-in-progress {
            background-color: #cce7ff;
            color: #004085;
        }
        
        .priority-badge {
            border-radius: 15px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .priority-high-badge {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .priority-medium-badge {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .priority-low-badge {
            background-color: #e8f5e8;
            color: #2e7d32;
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
                <a href="employees.php" class="nav-link text-white mb-2">
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
                <a href="proposals.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded">
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
                <h2 class="mb-1">Proposal Management</h2>
                <p class="text-muted mb-0">Manage client proposals and project requests</p>
            </div>
            <div>
                <button class="btn btn-primary me-2" data-mdb-toggle="modal" data-mdb-target="#addProposalModal">
                    <i class="fas fa-plus me-2"></i>Add Proposal
                </button>
                <button class="btn btn-outline-primary d-md-none" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
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
                        <i class="fas fa-file-contract fa-2x text-primary mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_proposals']; ?></h3>
                        <p class="text-muted mb-0">Total Proposals</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['pending_proposals']; ?></h3>
                        <p class="text-muted mb-0">Pending Review</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['approved_proposals']; ?></h3>
                        <p class="text-muted mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x text-danger mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['rejected_proposals']; ?></h3>
                        <p class="text-muted mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Proposals List -->
        <div class="row">
            <?php foreach ($proposals as $proposal): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card proposal-card">
                    <div class="priority-indicator priority-<?php echo $proposal['priority']; ?>" 
                         title="<?php echo ucfirst($proposal['priority']); ?> Priority"></div>
                    
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1 me-3">
                                <h6 class="mb-1"><?php echo htmlspecialchars($proposal['business_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($proposal['contact_info']); ?></small>
                            </div>
                            <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                <?php echo ucfirst(str_replace('-', ' ', $proposal['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Service Requested</small>
                            <span class="fw-medium"><?php echo htmlspecialchars($proposal['service_title'] ?? 'Custom Service'); ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Project Brief</small>
                            <p class="mb-0 small"><?php echo htmlspecialchars(substr($proposal['brief'], 0, 120)) . (strlen($proposal['brief']) > 120 ? '...' : ''); ?></p>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Budget Range</small>
                                <span class="fw-medium"><?php echo htmlspecialchars($proposal['budget_range'] ?? 'Not specified'); ?></span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Timeline</small>
                                <span><?php echo htmlspecialchars($proposal['timeline'] ?? 'Flexible'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Priority</small>
                                <span class="priority-badge priority-<?php echo $proposal['priority']; ?>-badge">
                                    <?php echo ucfirst($proposal['priority']); ?>
                                </span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Submitted</small>
                                <span><?php echo date('M d, Y', strtotime($proposal['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($proposal['admin_notes']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Admin Notes</small>
                            <p class="mb-0 small text-info"><?php echo htmlspecialchars($proposal['admin_notes']); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($proposal['approved_by']): ?>
                        <div class="mb-3">
                            <small class="text-muted d-block">Processed By</small>
                            <span class="small"><?php echo htmlspecialchars($proposal['approver_first_name'] . ' ' . $proposal['approver_last_name']); ?></span>
                            <small class="text-muted d-block"><?php echo date('M d, Y H:i', strtotime($proposal['approved_at'])); ?></small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="viewProposal(<?php echo $proposal['id']; ?>)">
                                <i class="fas fa-eye me-1"></i>View
                            </button>
                            <?php if ($proposal['status'] === 'pending'): ?>
                            <button class="btn btn-success btn-sm" onclick="updateStatus(<?php echo $proposal['id']; ?>, 'approved')">
                                <i class="fas fa-check me-1"></i>Approve
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $proposal['id']; ?>, 'rejected')">
                                <i class="fas fa-times me-1"></i>Reject
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Proposal Modal -->
    <div class="modal fade" id="addProposalModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Proposal</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_proposal">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="businessName" class="form-label">Business Name *</label>
                                <input type="text" class="form-control" name="business_name" id="businessName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contactInfo" class="form-label">Contact Information *</label>
                                <input type="text" class="form-control" name="contact_info" id="contactInfo" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="serviceId" class="form-label">Service</label>
                                <select class="form-select" name="service_id" id="serviceId">
                                    <option value="">Custom Service</option>
                                    <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>">
                                        <?php echo htmlspecialchars($service['title']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="brief" class="form-label">Project Brief *</label>
                            <textarea class="form-control" name="brief" id="brief" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="budgetRange" class="form-label">Budget Range</label>
                                <select class="form-select" name="budget_range" id="budgetRange">
                                    <option value="">Not specified</option>
                                    <option value="Under PKR 50,000">Under PKR 50,000</option>
                                    <option value="PKR 50,000 - 100,000">PKR 50,000 - 100,000</option>
                                    <option value="PKR 100,000 - 250,000">PKR 100,000 - 250,000</option>
                                    <option value="PKR 250,000 - 500,000">PKR 250,000 - 500,000</option>
                                    <option value="Above PKR 500,000">Above PKR 500,000</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="timeline" class="form-label">Timeline</label>
                                <select class="form-select" name="timeline" id="timeline">
                                    <option value="">Flexible</option>
                                    <option value="1 week">1 week</option>
                                    <option value="2 weeks">2 weeks</option>
                                    <option value="1 month">1 month</option>
                                    <option value="2-3 months">2-3 months</option>
                                    <option value="3+ months">3+ months</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Proposal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Proposal Status</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="proposal_id" id="updateProposalId">
                        <input type="hidden" name="new_status" id="updateNewStatus">
                        
                        <div class="mb-3">
                            <label for="statusNotes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" name="notes" id="statusNotes" rows="3" 
                                      placeholder="Add any notes about this status change..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateStatusBtn">Update Status</button>
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
        
        function viewProposal(proposalId) {
            // In a real implementation, you would show a detailed view modal
            alert('View proposal details for ID: ' + proposalId);
        }
        
        function updateStatus(proposalId, newStatus) {
            document.getElementById('updateProposalId').value = proposalId;
            document.getElementById('updateNewStatus').value = newStatus;
            
            const statusBtn = document.getElementById('updateStatusBtn');
            const modal = new mdb.Modal(document.getElementById('updateStatusModal'));
            
            if (newStatus === 'approved') {
                statusBtn.textContent = 'Approve Proposal';
                statusBtn.className = 'btn btn-success';
            } else if (newStatus === 'rejected') {
                statusBtn.textContent = 'Reject Proposal';
                statusBtn.className = 'btn btn-danger';
            }
            
            modal.show();
        }
    </script>
</body>
</html>
