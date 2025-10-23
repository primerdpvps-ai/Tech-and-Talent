<?php
/**
 * TTS PMS - Candidate Dashboard
 * Dashboard for candidates to apply for jobs and track applications
 */

require_once '../../config/init.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$db = Database::getInstance();
$user = ['first_name' => 'Demo', 'last_name' => 'Candidate', 'email' => $_SESSION['email']];

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'apply_job':
                $jobId = (int)($_POST['job_id'] ?? 0);
                $coverLetter = $_POST['cover_letter'] ?? '';
                
                if ($jobId && $coverLetter) {
                    $db->insert('tts_applications', [
                        'user_id' => $_SESSION['user_id'],
                        'job_id' => $jobId,
                        'cover_letter' => $coverLetter,
                        'status' => 'pending',
                        'applied_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = 'Application submitted successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get available jobs
$jobs = [
    ['id' => 1, 'title' => 'PHP Developer', 'department' => 'Development', 'type' => 'Full-time', 'salary' => '50000-80000', 'posted' => '2025-01-15'],
    ['id' => 2, 'title' => 'Data Entry Specialist', 'department' => 'Operations', 'type' => 'Part-time', 'salary' => '25000-35000', 'posted' => '2025-01-10'],
    ['id' => 3, 'title' => 'Digital Marketing Assistant', 'department' => 'Marketing', 'type' => 'Contract', 'salary' => '30000-45000', 'posted' => '2025-01-08']
];

// Get user applications
$applications = [
    ['id' => 1, 'job_title' => 'Data Entry Specialist', 'status' => 'under_review', 'applied_at' => '2025-01-12 10:30:00'],
    ['id' => 2, 'job_title' => 'PHP Developer', 'status' => 'pending', 'applied_at' => '2025-01-14 14:15:00']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Dashboard - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 40px 0; }
        .job-card, .application-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; transition: transform 0.3s ease; }
        .job-card:hover { transform: translateY(-3px); }
        .stat-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-under_review { background-color: #cce7ff; color: #004085; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-3">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
                    <p class="lead mb-0">Find and apply for exciting job opportunities</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="../../auth/logout.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-briefcase fa-2x text-primary mb-3"></i>
                        <h3><?php echo count($jobs); ?></h3>
                        <p class="text-muted mb-0">Available Jobs</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-paper-plane fa-2x text-success mb-3"></i>
                        <h3><?php echo count($applications); ?></h3>
                        <p class="text-muted mb-0">Applications Sent</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-eye fa-2x text-info mb-3"></i>
                        <h3>1</h3>
                        <p class="text-muted mb-0">Under Review</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-star fa-2x text-warning mb-3"></i>
                        <h3>85%</h3>
                        <p class="text-muted mb-0">Profile Score</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Available Jobs -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-briefcase me-2"></i>Available Positions</h4>
                    <button class="btn btn-outline-primary btn-sm" data-mdb-toggle="modal" data-mdb-target="#filterModal">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>

                <?php foreach ($jobs as $job): ?>
                <div class="card job-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($job['department']); ?> • <?php echo htmlspecialchars($job['type']); ?></p>
                            </div>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($job['type']); ?></span>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Salary Range</small>
                                <strong>PKR <?php echo htmlspecialchars($job['salary']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Posted</small>
                                <span><?php echo date('M d, Y', strtotime($job['posted'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm" onclick="applyJob(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>')">
                                <i class="fas fa-paper-plane me-1"></i>Apply Now
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="viewJob(<?php echo $job['id']; ?>)">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- My Applications -->
            <div class="col-lg-4">
                <h4 class="mb-3"><i class="fas fa-list me-2"></i>My Applications</h4>
                
                <?php foreach ($applications as $app): ?>
                <div class="card application-card">
                    <div class="card-body">
                        <h6 class="mb-2"><?php echo htmlspecialchars($app['job_title']); ?></h6>
                        <div class="mb-2">
                            <span class="badge status-<?php echo $app['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                            </span>
                        </div>
                        <small class="text-muted">Applied: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></small>
                        <div class="mt-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="viewApplication(<?php echo $app['id']; ?>)">
                                <i class="fas fa-eye me-1"></i>View
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Profile Completion -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-user me-2"></i>Profile Completion</h6>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                        <small class="text-muted">85% Complete</small>
                        <div class="mt-3">
                            <button class="btn btn-outline-success btn-sm w-100">
                                <i class="fas fa-edit me-1"></i>Complete Profile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apply Job Modal -->
    <div class="modal fade" id="applyJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply for Position</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="apply_job">
                        <input type="hidden" name="job_id" id="applyJobId">
                        
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" id="applyJobTitle" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cover Letter *</label>
                            <textarea class="form-control" name="cover_letter" rows="5" required 
                                      placeholder="Tell us why you're perfect for this role..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    <script>
        function applyJob(jobId, jobTitle) {
            document.getElementById('applyJobId').value = jobId;
            document.getElementById('applyJobTitle').value = jobTitle;
            const modal = new mdb.Modal(document.getElementById('applyJobModal'));
            modal.show();
        }
        
        function viewJob(jobId) {
            alert('View job details for ID: ' + jobId);
        }
        
        function viewApplication(appId) {
            alert('View application details for ID: ' + appId);
        }
    </script>
</body>
</html>
