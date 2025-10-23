<?php
/**
 * TTS PMS - Manager Profile
 * Profile management for managers
 */

// Load configuration and check authentication
require_once '../../../../config/init.php';
require_once '../../../../config/auth_check.php';

// Check if user has manager role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$pageTitle = 'My Profile';
$currentPage = 'profile';

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        $updateData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('tts_users', $updateData, 'id = ?', [$_SESSION['user_id']]);
        
        // Update session data
        $_SESSION['name'] = $updateData['first_name'] . ' ' . $updateData['last_name'];
        
        $success = 'Profile updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update profile: ' . $e->getMessage();
    }
}

// Load current user data and team information
try {
    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT * FROM tts_users WHERE id = ?", [$_SESSION['user_id']]);
    
    // Get team members (employees under this manager)
    $teamMembers = $db->fetchAll("
        SELECT u.*, te.date, te.total_hours, te.status as work_status
        FROM tts_users u
        LEFT JOIN tts_time_entries te ON u.id = te.user_id AND te.date = CURDATE()
        WHERE u.role IN ('employee', 'new_employee') AND u.status = 'ACTIVE'
        ORDER BY u.first_name, u.last_name
    ");
    
    // Get recent tasks assigned by this manager
    $assignedTasks = $db->fetchAll("
        SELECT dt.*, u.first_name, u.last_name
        FROM tts_daily_tasks dt
        JOIN tts_users u ON dt.user_id = u.id
        WHERE dt.assigned_by = ?
        ORDER BY dt.created_at DESC
        LIMIT 10
    ", [$_SESSION['user_id']]);
    
    // Get pending leave requests
    $pendingLeaves = $db->fetchAll("
        SELECT lr.*, u.first_name, u.last_name
        FROM tts_leave_requests lr
        JOIN tts_users u ON lr.user_id = u.id
        WHERE lr.status = 'pending'
        ORDER BY lr.created_at ASC
        LIMIT 5
    ");
    
} catch (Exception $e) {
    $user = [];
    $teamMembers = [];
    $assignedTasks = [];
    $pendingLeaves = [];
}

// Calculate team stats
$totalTeam = count($teamMembers);
$activeToday = count(array_filter($teamMembers, fn($member) => $member['work_status'] === 'active'));
$completedToday = count(array_filter($teamMembers, fn($member) => $member['work_status'] === 'completed'));
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
    <!-- MDB UI Kit -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    
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
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            font-size: 3rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
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
                    <a class="nav-link active" href="profile.php">
                        <i class="fas fa-user me-2"></i>My Profile
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i>Reports
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-user me-2"></i>My Profile</h2>
            <div class="text-muted">
                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                        <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="badge bg-light text-dark mt-2">
                            Manager
                        </span>
                    </div>
                    
                    <div class="card-body p-4">
                        <h5 class="mb-4">Personal Information</h5>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-outline mb-4">
                                        <input type="text" id="first_name" name="first_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                        <label class="form-label" for="first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-outline mb-4">
                                        <input type="text" id="last_name" name="last_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                        <label class="form-label" for="last_name">Last Name</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-outline mb-4">
                                <input type="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <label class="form-label" for="email">Email Address</label>
                                <div class="form-text">Email cannot be changed. Contact admin if needed.</div>
                            </div>
                            
                            <div class="form-outline mb-4">
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                <label class="form-label" for="phone">Phone Number</label>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); border: none;">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Team Overview -->
            <div class="col-md-4">
                <!-- Team Stats -->
                <div class="card stat-card mb-3">
                    <h5 class="card-title">Team Overview</h5>
                    <div class="row">
                        <div class="col-4">
                            <div class="border-end border-light">
                                <h3><?php echo $totalTeam; ?></h3>
                                <small>Total</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border-end border-light">
                                <h3><?php echo $activeToday; ?></h3>
                                <small>Active</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <h3><?php echo $completedToday; ?></h3>
                            <small>Done</small>
                        </div>
                    </div>
                </div>

                <!-- Pending Leave Requests -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-times me-2"></i>Pending Leaves</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingLeaves)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">No pending requests</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pendingLeaves as $leave): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <h6 class="mb-1"><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></h6>
                                <small class="text-muted d-block">
                                    <?php echo ucfirst($leave['leave_type']); ?> Leave
                                </small>
                                <small class="text-muted d-block">
                                    <?php echo date('M j', strtotime($leave['start_date'])); ?> - 
                                    <?php echo date('M j', strtotime($leave['end_date'])); ?>
                                </small>
                                <div class="mt-2">
                                    <button class="btn btn-success btn-sm me-1">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Tasks -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tasks me-2"></i>Recent Tasks Assigned</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignedTasks)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                                <p class="mb-0">No tasks assigned yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($assignedTasks, 0, 5) as $task): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                <small class="text-muted d-block">
                                    Assigned to: <?php echo htmlspecialchars($task['first_name'] . ' ' . $task['last_name']); ?>
                                </small>
                                <span class="badge bg-<?php 
                                    echo match($task['status']) {
                                        'pending' => 'warning',
                                        'in_progress' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?> mt-1">
                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-outline-primary btn-sm">
                                    View All Tasks
                                </a>
                            </div>
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
