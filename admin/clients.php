<?php
/**
 * TTS PMS - Client Management
 * Admin panel for managing clients and their projects
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

// Handle client actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_client':
                $clientData = [
                    'business_name' => $_POST['business_name'] ?? '',
                    'contact_person' => $_POST['contact_person'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'address' => $_POST['address'] ?? '',
                    'city' => $_POST['city'] ?? '',
                    'country' => $_POST['country'] ?? 'Pakistan',
                    'industry' => $_POST['industry'] ?? '',
                    'website' => $_POST['website'] ?? '',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $db->insert('tts_clients', $clientData);
                $message = 'Client added successfully!';
                $messageType = 'success';
                break;
                
            case 'update_client':
                $clientId = (int)($_POST['client_id'] ?? 0);
                if ($clientId) {
                    $updateData = [
                        'business_name' => $_POST['business_name'] ?? '',
                        'contact_person' => $_POST['contact_person'] ?? '',
                        'email' => $_POST['email'] ?? '',
                        'phone' => $_POST['phone'] ?? '',
                        'address' => $_POST['address'] ?? '',
                        'city' => $_POST['city'] ?? '',
                        'country' => $_POST['country'] ?? 'Pakistan',
                        'industry' => $_POST['industry'] ?? '',
                        'website' => $_POST['website'] ?? '',
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $db->update('tts_clients', $updateData, 'id = ?', [$clientId]);
                    $message = 'Client updated successfully!';
                    $messageType = 'success';
                }
                break;
                
            case 'update_status':
                $clientId = (int)($_POST['client_id'] ?? 0);
                $newStatus = $_POST['new_status'] ?? '';
                if ($clientId && $newStatus) {
                    $db->update('tts_clients', 
                        ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$clientId]
                    );
                    $message = 'Client status updated successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get clients with project counts
$clients = $db->fetchAll("
    SELECT 
        c.*,
        COUNT(p.id) as project_count,
        SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active_projects,
        SUM(p.total_amount) as total_value
    FROM tts_clients c
    LEFT JOIN tts_projects p ON c.id = p.client_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

// Get statistics
$stats = [
    'total_clients' => $db->count('tts_clients'),
    'active_clients' => $db->count('tts_clients', "status = 'active'"),
    'inactive_clients' => $db->count('tts_clients', "status = 'inactive'"),
    'total_projects' => $db->count('tts_projects')
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - TTS PMS</title>
    
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
        
        .client-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .client-card:hover {
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
        
        .client-avatar {
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
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
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
                <a href="clients.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded">
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
                <h2 class="mb-1">Client Management</h2>
                <p class="text-muted mb-0">Manage your clients and their projects</p>
            </div>
            <div>
                <button class="btn btn-primary me-2" data-mdb-toggle="modal" data-mdb-target="#addClientModal">
                    <i class="fas fa-plus me-2"></i>Add Client
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
                        <i class="fas fa-handshake fa-2x text-primary mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_clients']; ?></h3>
                        <p class="text-muted mb-0">Total Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-2x text-success mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['active_clients']; ?></h3>
                        <p class="text-muted mb-0">Active Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-times fa-2x text-danger mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['inactive_clients']; ?></h3>
                        <p class="text-muted mb-0">Inactive Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-project-diagram fa-2x text-info mb-3"></i>
                        <h3 class="mb-1"><?php echo $stats['total_projects']; ?></h3>
                        <p class="text-muted mb-0">Total Projects</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clients List -->
        <div class="row">
            <?php foreach ($clients as $client): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card client-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="client-avatar me-3">
                                <?php echo strtoupper(substr($client['business_name'], 0, 2)); ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($client['business_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($client['contact_person']); ?></small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-mdb-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="editClient(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-edit me-2"></i>Edit Details
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewProjects(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-project-diagram me-2"></i>View Projects
                                    </a></li>
                                    <?php if ($client['status'] === 'active'): ?>
                                    <li><a class="dropdown-item text-warning" href="#" onclick="updateStatus(<?php echo $client['id']; ?>, 'inactive')">
                                        <i class="fas fa-pause me-2"></i>Deactivate
                                    </a></li>
                                    <?php else: ?>
                                    <li><a class="dropdown-item text-success" href="#" onclick="updateStatus(<?php echo $client['id']; ?>, 'active')">
                                        <i class="fas fa-play me-2"></i>Activate
                                    </a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Status</small>
                                <span class="status-badge status-<?php echo $client['status']; ?>">
                                    <?php echo ucfirst($client['status']); ?>
                                </span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Industry</small>
                                <span><?php echo htmlspecialchars($client['industry'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <small class="text-muted d-block">Contact</small>
                                <div>
                                    <i class="fas fa-envelope me-1"></i>
                                    <small><?php echo htmlspecialchars($client['email']); ?></small>
                                </div>
                                <?php if ($client['phone']): ?>
                                <div>
                                    <i class="fas fa-phone me-1"></i>
                                    <small><?php echo htmlspecialchars($client['phone']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <small class="text-muted d-block">Location</small>
                                <span><?php echo htmlspecialchars($client['city'] . ', ' . $client['country']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($client['website']): ?>
                        <div class="row mb-3">
                            <div class="col-12">
                                <small class="text-muted d-block">Website</small>
                                <a href="<?php echo htmlspecialchars($client['website']); ?>" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    <?php echo htmlspecialchars($client['website']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Projects</small>
                                <strong><?php echo $client['project_count'] ?? 0; ?></strong>
                                <small class="text-success">(<?php echo $client['active_projects'] ?? 0; ?> active)</small>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Total Value</small>
                                <strong>PKR <?php echo number_format($client['total_value'] ?? 0); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Client</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_client">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="businessName" class="form-label">Business Name *</label>
                                <input type="text" class="form-control" name="business_name" id="businessName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contactPerson" class="form-label">Contact Person *</label>
                                <input type="text" class="form-control" name="contact_person" id="contactPerson" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="phone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="address" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" name="city" id="city">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" id="country" value="Pakistan">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="industry" class="form-label">Industry</label>
                                <select class="form-select" name="industry" id="industry">
                                    <option value="">Select Industry</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Education">Education</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" id="website" placeholder="https://">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Client Modal -->
    <div class="modal fade" id="editClientModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Client</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_client">
                        <input type="hidden" name="client_id" id="editClientId">
                        
                        <!-- Same form fields as add modal with edit prefix -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editBusinessName" class="form-label">Business Name *</label>
                                <input type="text" class="form-control" name="business_name" id="editBusinessName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editContactPerson" class="form-label">Contact Person *</label>
                                <input type="text" class="form-control" name="contact_person" id="editContactPerson" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="editEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPhone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" id="editPhone">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editAddress" class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="editAddress" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editCity" class="form-label">City</label>
                                <input type="text" class="form-control" name="city" id="editCity">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCountry" class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" id="editCountry">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editIndustry" class="form-label">Industry</label>
                                <select class="form-select" name="industry" id="editIndustry">
                                    <option value="">Select Industry</option>
                                    <option value="Technology">Technology</option>
                                    <option value="Healthcare">Healthcare</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Education">Education</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editWebsite" class="form-label">Website</label>
                                <input type="url" class="form-control" name="website" id="editWebsite">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Client</button>
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
        
        function editClient(clientId) {
            // In a real implementation, you would fetch client data via AJAX
            // For now, we'll just show the modal
            document.getElementById('editClientId').value = clientId;
            const modal = new mdb.Modal(document.getElementById('editClientModal'));
            modal.show();
        }
        
        function viewProjects(clientId) {
            window.location.href = 'projects.php?client_id=' + clientId;
        }
        
        function updateStatus(clientId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this client?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="client_id" value="${clientId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
