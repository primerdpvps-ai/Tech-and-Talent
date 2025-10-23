<?php
/**
 * TTS PMS - Candidate Dashboard
 * Job application and tracking interface
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has candidate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'candidate') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Candidate dashboard error: " . $e->getMessage());
    $db = null;
}

$message = '';
$messageType = '';

// Demo job positions
$jobPositions = [
    [
        'id' => 1,
        'title' => 'Data Entry Specialist',
        'department' => 'Operations',
        'type' => 'Part-time',
        'description' => 'Accurate data entry and validation for client projects',
        'requirements' => ['Typing speed 40+ WPM', 'Attention to detail', 'Basic Excel skills'],
        'salary_range' => 'PKR 25,000 - 35,000',
        'status' => 'open',
        'posted_date' => date('Y-m-d', strtotime('-3 days'))
    ],
    [
        'id' => 2,
        'title' => 'Junior Data Analyst',
        'department' => 'Analytics',
        'type' => 'Full-time',
        'description' => 'Support data analysis projects and report generation',
        'requirements' => ['Excel proficiency', 'Basic SQL knowledge', 'Analytical thinking'],
        'salary_range' => 'PKR 40,000 - 55,000',
        'status' => 'open',
        'posted_date' => date('Y-m-d', strtotime('-1 day'))
    ],
    [
        'id' => 3,
        'title' => 'Database Administrator Trainee',
        'department' => 'IT',
        'type' => 'Full-time',
        'description' => 'Learn database management and maintenance',
        'requirements' => ['Basic database knowledge', 'Problem-solving skills', 'Willingness to learn'],
        'salary_range' => 'PKR 35,000 - 45,000',
        'status' => 'open',
        'posted_date' => date('Y-m-d', strtotime('-5 days'))
    ]
];

// Demo applications for this user
$myApplications = [
    [
        'id' => 1,
        'job_title' => 'Data Entry Specialist',
        'applied_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'status' => 'under_review',
        'notes' => 'Application received and under initial review'
    ]
];

// Handle job application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    $jobId = (int)($_POST['job_id'] ?? 0);
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    
    if ($jobId && $coverLetter) {
        // In real implementation, save to database
        $message = 'Application submitted successfully! We will review your application and contact you within 48 hours.';
        $messageType = 'success';
        
        // Add to demo applications
        $jobTitle = '';
        foreach ($jobPositions as $job) {
            if ($job['id'] == $jobId) {
                $jobTitle = $job['title'];
                break;
            }
        }
        
        $myApplications[] = [
            'id' => count($myApplications) + 1,
            'job_title' => $jobTitle,
            'applied_date' => date('Y-m-d H:i:s'),
            'status' => 'submitted',
            'notes' => 'Application just submitted'
        ];
    } else {
        $message = 'Please fill in all required fields.';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Dashboard - TTS PMS</title>
    
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
        
        .dashboard-header {
            background: linear-gradient(135deg, #1266f1, #39c0ed);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .job-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-3px);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        
        .status-open {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .status-submitted {
            background: linear-gradient(135deg, #39c0ed, #0dcaf0);
            color: white;
        }
        
        .status-under_review {
            background: linear-gradient(135deg, #fbbd08, #ffc107);
            color: white;
        }
        
        .status-accepted {
            background: linear-gradient(135deg, #00b74a, #28a745);
            color: white;
        }
        
        .status-rejected {
            background: linear-gradient(135deg, #f93154, #dc3545);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Candidate'); ?>!</h2>
                    <p class="mb-0">Explore job opportunities and track your applications</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-mdb-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../../../../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-briefcase fa-2x text-primary mb-2"></i>
                        <h4 class="mb-1"><?php echo count($jobPositions); ?></h4>
                        <p class="text-muted mb-0">Available Jobs</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-paper-plane fa-2x text-success mb-2"></i>
                        <h4 class="mb-1"><?php echo count($myApplications); ?></h4>
                        <p class="text-muted mb-0">My Applications</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h4 class="mb-1"><?php echo count(array_filter($myApplications, function($app) { return $app['status'] === 'under_review'; })); ?></h4>
                        <p class="text-muted mb-0">Under Review</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                        <h4 class="mb-1"><?php echo count(array_filter($myApplications, function($app) { return $app['status'] === 'accepted'; })); ?></h4>
                        <p class="text-muted mb-0">Accepted</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Available Jobs -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Available Positions</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($jobPositions as $job): ?>
                        <div class="job-card card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($job['department']); ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($job['type']); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge status-<?php echo $job['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $job['status'])); ?>
                                    </span>
                                </div>
                                
                                <p class="mb-3"><?php echo htmlspecialchars($job['description']); ?></p>
                                
                                <div class="mb-3">
                                    <h6 class="mb-2">Requirements:</h6>
                                    <ul class="list-unstyled mb-0">
                                        <?php foreach ($job['requirements'] as $req): ?>
                                        <li><i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars($req); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-primary"><?php echo htmlspecialchars($job['salary_range']); ?></strong>
                                        <br>
                                        <small class="text-muted">Posted: <?php echo date('M j, Y', strtotime($job['posted_date'])); ?></small>
                                    </div>
                                    <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#applyModal<?php echo $job['id']; ?>">
                                        <i class="fas fa-paper-plane me-2"></i>Apply Now
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Application Modal -->
                        <div class="modal fade" id="applyModal<?php echo $job['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Apply for <?php echo htmlspecialchars($job['title']); ?></h5>
                                        <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="apply_job" value="1">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="cover_letter<?php echo $job['id']; ?>" class="form-label">Cover Letter</label>
                                                <textarea class="form-control" id="cover_letter<?php echo $job['id']; ?>" name="cover_letter" 
                                                          rows="5" required placeholder="Tell us why you're interested in this position..."></textarea>
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
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- My Applications -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>My Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($myApplications)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No applications yet</p>
                            <p class="small text-muted">Apply to jobs to see them here</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($myApplications as $app): ?>
                        <div class="card mb-3">
                            <div class="card-body p-3">
                                <h6 class="mb-2"><?php echo htmlspecialchars($app['job_title']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('M j', strtotime($app['applied_date'])); ?></small>
                                </div>
                                <p class="small text-muted mb-0"><?php echo htmlspecialchars($app['notes']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
</body>
</html>
