<?php
/**
 * TTS PMS - Application Details Modal Content
 * AJAX endpoint for loading application details
 */

// Load configuration and check admin access
require_once '../config/init.php';

// Start session
session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Access denied</div>';
    exit;
}

$applicationId = (int)($_GET['id'] ?? 0);

if (!$applicationId) {
    echo '<div class="alert alert-danger">Invalid application ID</div>';
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get application details
    $application = $db->fetchOne(
        'SELECT a.*, um.user_id as user_display_id, um.gmail_verified, um.mobile_verified, um.birthday, um.city, um.province, um.country
         FROM tts_applications a 
         JOIN tts_users_meta um ON a.user_id = um.user_id 
         WHERE a.id = ?',
        [$applicationId]
    );
    
    if (!$application) {
        echo '<div class="alert alert-danger">Application not found</div>';
        exit;
    }
    
    // Get evaluation data if exists
    $evaluation = $db->fetchOne(
        'SELECT * FROM tts_evaluations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1',
        [$application['user_id']]
    );
    
    // Get employment record if exists
    $employment = $db->fetchOne(
        'SELECT * FROM tts_employment WHERE user_id = ?',
        [$application['user_id']]
    );
    
} catch (Exception $e) {
    log_message('error', 'Application details fetch failed: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Failed to load application details</div>';
    exit;
}

// Status badge classes
$statusClass = [
    'under_review' => 'warning',
    'approved' => 'success',
    'rejected' => 'danger'
][$application['status']] ?? 'secondary';

// Contract variant descriptions
$contractDescriptions = [
    'standard' => 'Standard employment contract for adults (18+)',
    'minor' => 'Special contract for minors (under 18) with guardian consent',
    'senior' => 'Senior employee contract with additional benefits'
];
?>

<div class="row">
    <!-- Basic Information -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-user me-2"></i>Basic Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Application ID:</strong></td>
                        <td>#<?php echo $application['id']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>User ID:</strong></td>
                        <td><span class="badge bg-light text-dark">User #<?php echo $application['user_id']; ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Job Type:</strong></td>
                        <td>
                            <span class="badge bg-<?php echo $application['job_type'] === 'full' ? 'primary' : 'info'; ?>">
                                <?php echo ucfirst($application['job_type']); ?>-time
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Contract Type:</strong></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo ucfirst($application['contract_variant']); ?></span>
                            <br><small class="text-muted"><?php echo $contractDescriptions[$application['contract_variant']] ?? ''; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge bg-<?php echo $statusClass; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $application['status'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Submitted:</strong></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($application['submitted_at'])); ?></td>
                    </tr>
                    <?php if ($application['decided_at']): ?>
                    <tr>
                        <td><strong>Decision Date:</strong></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($application['decided_at'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <!-- User Profile Information -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-id-card me-2"></i>Profile Information</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Email Verified:</strong></td>
                        <td>
                            <?php if ($application['gmail_verified']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times"></i> Not Verified</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Mobile Verified:</strong></td>
                        <td>
                            <?php if ($application['mobile_verified']): ?>
                                <span class="badge bg-success"><i class="fas fa-check"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-times"></i> Not Verified</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($application['birthday']): ?>
                    <tr>
                        <td><strong>Date of Birth:</strong></td>
                        <td>
                            <?php 
                            $age = date_diff(date_create($application['birthday']), date_create('today'))->y;
                            echo date('M j, Y', strtotime($application['birthday'])) . " (Age: {$age})";
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><strong>Location:</strong></td>
                        <td>
                            <?php 
                            $location = array_filter([$application['city'], $application['province'], $application['country']]);
                            echo implode(', ', $location) ?: 'Not specified';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Documents Section -->
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-folder me-2"></i>Submitted Documents</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="text-center">
                    <div class="mb-2">
                        <?php if ($application['kyc_cnic_front_id']): ?>
                            <i class="fas fa-file-image fa-2x text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-file-image fa-2x text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <small class="d-block">CNIC Front</small>
                    <?php if ($application['kyc_cnic_front_id']): ?>
                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument('<?php echo $application['kyc_cnic_front_id']; ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    <?php else: ?>
                        <span class="badge bg-warning mt-1">Missing</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="text-center">
                    <div class="mb-2">
                        <?php if ($application['kyc_cnic_back_id']): ?>
                            <i class="fas fa-file-image fa-2x text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-file-image fa-2x text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <small class="d-block">CNIC Back</small>
                    <?php if ($application['kyc_cnic_back_id']): ?>
                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument('<?php echo $application['kyc_cnic_back_id']; ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    <?php else: ?>
                        <span class="badge bg-warning mt-1">Missing</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="text-center">
                    <div class="mb-2">
                        <?php if ($application['utility_bill_id']): ?>
                            <i class="fas fa-file-alt fa-2x text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-file-alt fa-2x text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <small class="d-block">Utility Bill</small>
                    <?php if ($application['utility_bill_id']): ?>
                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument('<?php echo $application['utility_bill_id']; ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    <?php else: ?>
                        <span class="badge bg-warning mt-1">Missing</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="text-center">
                    <div class="mb-2">
                        <?php if ($application['selfie_id']): ?>
                            <i class="fas fa-camera fa-2x text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-camera fa-2x text-muted"></i>
                        <?php endif; ?>
                    </div>
                    <small class="d-block">Live Selfie</small>
                    <?php if ($application['selfie_id']): ?>
                        <button class="btn btn-sm btn-outline-primary mt-1" onclick="viewDocument('<?php echo $application['selfie_id']; ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    <?php else: ?>
                        <span class="badge bg-warning mt-1">Missing</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($application['contract_id']): ?>
        <hr>
        <div class="text-center">
            <h6>Signed Contract</h6>
            <button class="btn btn-outline-success" onclick="viewDocument('<?php echo $application['contract_id']; ?>')">
                <i class="fas fa-file-contract me-1"></i> View Signed Contract
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Evaluation Results -->
<?php if ($evaluation): ?>
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Pre-Requirements Evaluation</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Age:</strong></td>
                        <td><?php echo $evaluation['age']; ?> years</td>
                    </tr>
                    <tr>
                        <td><strong>Device Type:</strong></td>
                        <td><?php echo htmlspecialchars($evaluation['device_type']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>RAM:</strong></td>
                        <td><?php echo htmlspecialchars($evaluation['ram_text']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Processor:</strong></td>
                        <td><?php echo htmlspecialchars($evaluation['processor_text']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Internet:</strong></td>
                        <td>
                            <?php if ($evaluation['has_stable_internet']): ?>
                                <span class="badge bg-success">Stable</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Unstable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>ISP:</strong></td>
                        <td><?php echo htmlspecialchars($evaluation['provider']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Speed:</strong></td>
                        <td><?php echo htmlspecialchars($evaluation['link_speed']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Users Sharing:</strong></td>
                        <td><?php echo $evaluation['users_sharing']; ?> users</td>
                    </tr>
                    <tr>
                        <td><strong>Profession:</strong></td>
                        <td><?php echo htmlspecialchars($evaluation['profession']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Evaluation Result:</strong></td>
                        <td>
                            <?php
                            $resultClass = [
                                'eligible' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'danger'
                            ][$evaluation['result']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $resultClass; ?>">
                                <?php echo ucfirst($evaluation['result']); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if ($evaluation['reasons']): ?>
        <hr>
        <h6>Evaluation Notes:</h6>
        <?php
        $reasons = json_decode($evaluation['reasons'], true);
        if (is_array($reasons) && !empty($reasons)) {
            echo '<ul class="mb-0">';
            foreach ($reasons as $reason) {
                echo '<li>' . htmlspecialchars($reason) . '</li>';
            }
            echo '</ul>';
        }
        ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Employment Status -->
<?php if ($employment): ?>
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>Employment Status</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Current Role:</strong></td>
                        <td>
                            <span class="badge bg-primary">
                                <?php echo ucwords(str_replace('_', ' ', $employment['role'])); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Start Date:</strong></td>
                        <td><?php echo date('M j, Y', strtotime($employment['start_date'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Training Status:</strong></td>
                        <td>
                            <?php if ($employment['training_completed']): ?>
                                <span class="badge bg-success">Completed</span>
                                <?php if ($employment['training_completed_at']): ?>
                                    <br><small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($employment['training_completed_at'])); ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-warning">In Progress</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>RDP Host:</strong></td>
                        <td><code><?php echo htmlspecialchars($employment['rdp_host'] ?? 'Not assigned'); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>RDP Username:</strong></td>
                        <td><code><?php echo htmlspecialchars($employment['rdp_username'] ?? 'Not assigned'); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Security Fund:</strong></td>
                        <td>
                            <?php if ($employment['security_fund_deducted']): ?>
                                <span class="badge bg-success">Deducted</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Rejection Reasons -->
<?php if ($application['status'] === 'rejected' && $application['reasons']): ?>
<div class="card">
    <div class="card-header">
        <h6 class="mb-0 text-danger"><i class="fas fa-times-circle me-2"></i>Rejection Reasons</h6>
    </div>
    <div class="card-body">
        <?php
        $reasons = json_decode($application['reasons'], true);
        if (isset($reasons['rejection_reason'])) {
            echo '<p class="mb-0">' . nl2br(htmlspecialchars($reasons['rejection_reason'])) . '</p>';
        }
        ?>
    </div>
</div>
<?php endif; ?>

<script>
function viewDocument(documentId) {
    // In a real implementation, this would open a secure document viewer
    alert('Document viewer would open here for document ID: ' + documentId);
    // You could implement a secure document viewing system here
}
</script>
