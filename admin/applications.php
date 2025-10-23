<?php
/**
 * TTS PMS - Applications Management
 * Admin panel for reviewing job applications
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
    
    // Check if tables exist, create demo data if needed
    if (!$db->tableExists('tts_applications')) {
        // Create some demo applications for testing
        $demoApplications = [
            [
                'id' => 1,
                'user_id' => 1,
                'job_id' => 1,
                'cover_letter' => 'I am interested in the data entry position and have 3 years of experience.',
                'status' => 'pending',
                'applied_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'job_id' => 2,
                'cover_letter' => 'I have extensive experience in data analytics and would love to contribute to your team.',
                'status' => 'under_review',
                'applied_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
    }
} catch (Exception $e) {
    error_log("Applications page error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $action = $_POST['action'] ?? '';
    $applicationId = (int)($_POST['application_id'] ?? 0);
    
    if ($action && $applicationId) {
        try {
            if ($action === 'approve') {
                $message = "Application #$applicationId has been approved successfully!";
                $messageType = 'success';
            } elseif ($action === 'reject') {
                $message = "Application #$applicationId has been rejected.";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            $message = 'Error processing application: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Demo applications data
$applications = [
    [
        'id' => 1,
        'applicant_name' => 'Ahmed Hassan',
        'applicant_email' => 'ahmed.hassan@email.com',
        'position' => 'Data Entry Specialist',
        'cover_letter' => 'I am interested in the data entry position and have 3 years of experience in accurate data processing.',
        'status' => 'pending',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'experience' => '3 years',
        'skills' => 'Excel, Data Processing, Typing Speed: 60 WPM'
    ],
    [
        'id' => 2,
        'applicant_name' => 'Sarah Khan',
        'applicant_email' => 'sarah.khan@email.com',
        'position' => 'Data Analytics Specialist',
        'cover_letter' => 'I have extensive experience in data analytics and would love to contribute to your team with my expertise in statistical analysis.',
        'status' => 'under_review',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'experience' => '5 years',
        'skills' => 'Python, R, SQL, Tableau, Power BI'
    ],
    [
        'id' => 3,
        'applicant_name' => 'Muhammad Ali',
        'applicant_email' => 'muhammad.ali@email.com',
        'position' => 'Database Administrator',
        'cover_letter' => 'Experienced database professional seeking to join your team. I have worked with various database systems.',
        'status' => 'approved',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'experience' => '7 years',
        'skills' => 'MySQL, PostgreSQL, MongoDB, Database Design'
    ],
    [
        'id' => 4,
        'applicant_name' => 'Fatima Sheikh',
        'applicant_email' => 'fatima.sheikh@email.com',
        'position' => 'Excel Specialist',
        'cover_letter' => 'I am proficient in advanced Excel functions and have experience in creating complex spreadsheets and automation.',
        'status' => 'pending',
        'applied_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'experience' => '4 years',
        'skills' => 'Advanced Excel, VBA, Macros, Pivot Tables'
    ]
];

// Filter applications based on status
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all') {
    $applications = array_filter($applications, function($app) use ($statusFilter) {
        return $app['status'] === $statusFilter;
    });
}

// Get statistics (use original applications array for accurate counts)
$originalApplications = [
    ['id' => 1, 'status' => 'pending'],
    ['id' => 2, 'status' => 'under_review'],
    ['id' => 3, 'status' => 'approved'],
    ['id' => 4, 'status' => 'pending']
];

$stats = [
    'total' => count($originalApplications),
    'pending' => count(array_filter($originalApplications, fn($app) => $app['status'] === 'pending')),
    'under_review' => count(array_filter($originalApplications, fn($app) => $app['status'] === 'under_review')),
    'approved' => count(array_filter($originalApplications, fn($app) => $app['status'] === 'approved')),
    'rejected' => count(array_filter($originalApplications, fn($app) => $app['status'] === 'rejected'))
];
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Applications Management</title>
    
    <!-- Bootstrap & MDB CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .page-header {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .table-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .badge-status {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }
        
        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 20px;
        }
        
        .application-row:hover {
            background-color: rgba(18, 102, 241, 0.05);
        }
        
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-1">Applications Management</h2>
                    <p class="mb-0">Review and manage job applications</p>
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
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['total']); ?></h3>
                        <p class="text-muted mb-0">Total Applications</p>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-warning mb-2">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['pending']); ?></h3>
                        <p class="text-muted mb-0">Pending Review</p>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['approved']); ?></h3>
                        <p class="text-muted mb-0">Approved</p>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="text-danger mb-2">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['rejected']); ?></h3>
                        <p class="text-muted mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="under_review" <?php echo $statusFilter === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select" id="job_type" name="job_type">
                        <option value="all" <?php echo $jobTypeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="full" <?php echo $jobTypeFilter === 'full' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="part" <?php echo $jobTypeFilter === 'part' ? 'selected' : ''; ?>>Part-time</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Apply Filters
                    </button>
                    <a href="applications.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
                
                <div class="col-md-3 text-end">
                    <small class="text-muted">
                        Showing <?php echo count($applications); ?> of <?php echo number_format($totalCount); ?> applications
                    </small>
                </div>
            </form>
        </div>
        
        <!-- Applications Table -->
        <div class="card table-card">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Job Applications</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No applications found</h5>
                    <p class="text-muted">No applications match your current filters.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Application ID</th>
                                <th>User ID</th>
                                <th>Job Type</th>
                                <th>Contract Type</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                            <tr class="application-row">
                                <td>
                                    <strong>#<?php echo $app['id']; ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">User #<?php echo $app['user_id']; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $app['job_type'] === 'full' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($app['job_type']); ?>-time
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($app['contract_variant']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'under_review' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ][$app['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-status bg-<?php echo $statusClass; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i A', strtotime($app['submitted_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="viewApplication(<?php echo $app['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($app['status'] === 'under_review'): ?>
                                        <button type="button" class="btn btn-sm btn-success btn-action" 
                                                onclick="approveApplication(<?php echo $app['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger btn-action" 
                                                onclick="rejectApplication(<?php echo $app['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Applications pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&job_type=<?php echo $jobTypeFilter; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&job_type=<?php echo $jobTypeFilter; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&job_type=<?php echo $jobTypeFilter; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
    
    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applicationDetails">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Application</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="application_id" id="rejectApplicationId">
                        
                        <div class="mb-3">
                            <label for="reasons" class="form-label">Rejection Reasons</label>
                            <textarea class="form-control" id="reasons" name="reasons" rows="4" 
                                      placeholder="Please provide specific reasons for rejection..." required></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action cannot be undone. The applicant will be notified of the rejection.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        function viewApplication(applicationId) {
            // Load application details via AJAX
            fetch(`application-details.php?id=${applicationId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('applicationDetails').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('applicationModal')).show();
                })
                .catch(error => {
                    console.error('Error loading application details:', error);
                    alert('Failed to load application details.');
                });
        }
        
        function approveApplication(applicationId) {
            if (confirm('Are you sure you want to approve this application? This will create an employee record.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="application_id" value="${applicationId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectApplication(applicationId) {
            document.getElementById('rejectApplicationId').value = applicationId;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
        
        // Auto-refresh every 2 minutes
        setInterval(function() {
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 120000);
    </script>
</body>
</html>
