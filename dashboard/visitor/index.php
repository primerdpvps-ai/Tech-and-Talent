<?php
/**
 * TTS PMS - Visitor Dashboard
 * Dashboard for visitors to complete evaluation
 */

// Load configuration and check access
require_once '../../config/init.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../../auth/sign-in.php');
    exit;
}

// Check if user has visitor role
if ($_SESSION['role'] !== 'visitor') {
    header('Location: ../../dashboard/');
    exit;
}

$db = Database::getInstance();

// Get user details
$user = $db->fetchOne("SELECT * FROM tts_users WHERE id = ?", [$_SESSION['user_id']]);
$userMeta = $db->fetchOne("SELECT * FROM tts_users_meta WHERE user_id = ?", [$_SESSION['user_id']]);

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'acknowledge_legal':
                $legalType = $_POST['legal_type'] ?? '';
                if ($legalType) {
                    // Record legal document acknowledgment
                    $db->insert('tts_legal_acknowledgments', [
                        'user_id' => $_SESSION['user_id'],
                        'document_type' => $legalType,
                        'acknowledged_at' => date('Y-m-d H:i:s'),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
                    $message = 'Legal document acknowledged successfully!';
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Check legal acknowledgments
$acknowledgedDocs = $db->fetchAll("
    SELECT document_type 
    FROM tts_legal_acknowledgments 
    WHERE user_id = ?
", [$_SESSION['user_id']]);

$acknowledged = array_column($acknowledgedDocs, 'document_type');
$allAcknowledged = in_array('terms', $acknowledged) && in_array('privacy', $acknowledged) && in_array('nda', $acknowledged);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Dashboard - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .dashboard-header { background: linear-gradient(135deg, #1266f1, #39c0ed); color: white; padding: 40px 0; }
        .progress-step { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .step-completed { border-left: 4px solid #00b74a; }
        .step-current { border-left: 4px solid #1266f1; }
        .step-pending { border-left: 4px solid #e9ecef; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-3">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h1>
                    <p class="lead mb-0">You're currently a visitor. Complete your evaluation to become a candidate.</p>
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
        <!-- Alert Messages -->
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Legal Documents Section -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Legal Documents</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">Please review and acknowledge our legal documents before proceeding with the evaluation.</p>
                        
                        <!-- Terms of Service -->
                        <div class="d-flex justify-content-between align-items-center p-3 mb-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <?php if (in_array('terms', $acknowledged)): ?>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php else: ?>
                                <i class="fas fa-file-alt text-muted me-2"></i>
                                <?php endif; ?>
                                <span class="fw-medium">Terms of Service</span>
                            </div>
                            <div>
                                <a href="../../legal/terms.php" target="_blank" class="btn btn-sm btn-outline-primary me-2">View</a>
                                <?php if (!in_array('terms', $acknowledged)): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="acknowledge_legal">
                                    <input type="hidden" name="legal_type" value="terms">
                                    <button type="submit" class="btn btn-sm btn-success">Acknowledge</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Privacy Policy -->
                        <div class="d-flex justify-content-between align-items-center p-3 mb-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <?php if (in_array('privacy', $acknowledged)): ?>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php else: ?>
                                <i class="fas fa-shield-alt text-muted me-2"></i>
                                <?php endif; ?>
                                <span class="fw-medium">Privacy Policy</span>
                            </div>
                            <div>
                                <a href="../../legal/privacy.php" target="_blank" class="btn btn-sm btn-outline-primary me-2">View</a>
                                <?php if (!in_array('privacy', $acknowledged)): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="acknowledge_legal">
                                    <input type="hidden" name="legal_type" value="privacy">
                                    <button type="submit" class="btn btn-sm btn-success">Acknowledge</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- NDA Agreement -->
                        <div class="d-flex justify-content-between align-items-center p-3 mb-3 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <?php if (in_array('nda', $acknowledged)): ?>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <?php else: ?>
                                <i class="fas fa-handshake text-muted me-2"></i>
                                <?php endif; ?>
                                <span class="fw-medium">NDA Agreement</span>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="viewNDA()">View</button>
                                <?php if (!in_array('nda', $acknowledged)): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="acknowledge_legal">
                                    <input type="hidden" name="legal_type" value="nda">
                                    <button type="submit" class="btn btn-sm btn-success">Acknowledge</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evaluation CTA -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100 bg-gradient text-white" style="background: linear-gradient(135deg, #1266f1, #39c0ed);">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <i class="fas fa-graduation-cap fa-4x mb-4 opacity-75"></i>
                        <h3 class="mb-3">Ready for Evaluation?</h3>
                        <p class="mb-4">Take our comprehensive assessment to demonstrate your skills and qualify for opportunities.</p>
                        
                        <?php if ($allAcknowledged): ?>
                        <a href="evaluation.php" class="btn btn-light btn-lg">
                            <i class="fas fa-play me-2"></i>Start Evaluation
                        </a>
                        <?php else: ?>
                        <button class="btn btn-light btn-lg" disabled>
                            <i class="fas fa-lock me-2"></i>Complete Legal Requirements First
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-route me-2"></i>Your Journey</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Step 1: Account Created -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="progress-step step-completed p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <h6 class="mb-0">Account Created</h6>
                                    </div>
                                    <p class="text-muted small mb-0">Welcome to TTS PMS! Your account is ready.</p>
                                </div>
                            </div>

                            <!-- Step 2: Legal Documents -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="progress-step <?php echo $allAcknowledged ? 'step-completed' : 'step-current'; ?> p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-<?php echo $allAcknowledged ? 'success' : 'primary'; ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <?php if ($allAcknowledged): ?>
                                            <i class="fas fa-check"></i>
                                            <?php else: ?>
                                            <span class="fw-bold">2</span>
                                            <?php endif; ?>
                                        </div>
                                        <h6 class="mb-0">Legal Documents</h6>
                                    </div>
                                    <p class="text-muted small mb-0">Review and acknowledge legal requirements.</p>
                                </div>
                            </div>

                            <!-- Step 3: Complete Evaluation -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="progress-step step-pending p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <span class="fw-bold">3</span>
                                        </div>
                                        <h6 class="mb-0 text-muted">Complete Evaluation</h6>
                                    </div>
                                    <p class="text-muted small mb-0">Demonstrate your technical skills and qualifications.</p>
                                </div>
                            </div>

                            <!-- Step 4: Become Candidate -->
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="progress-step step-pending p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <span class="fw-bold">4</span>
                                        </div>
                                        <h6 class="mb-0 text-muted">Become Candidate</h6>
                                    </div>
                                    <p class="text-muted small mb-0">Access job opportunities and submit applications.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <div class="d-flex">
                        <i class="fas fa-info-circle me-3 mt-1"></i>
                        <div>
                            <h6 class="alert-heading">Need Help?</h6>
                            <p class="mb-0">If you have questions about the evaluation process or need technical support, our team is here to help. Contact us at <strong>support@tts.com.pk</strong> or call <strong>+92 300 1234567</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    <script>
        function viewNDA() {
            alert('NDA document viewer would be implemented here. For now, please contact admin for NDA details.');
        }
    </script>
</body>
</html>
