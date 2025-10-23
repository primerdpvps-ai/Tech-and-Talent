<?php
require_once '../config/init.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'add_gig':
                $gigData = [
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'budget' => (float)$_POST['budget'],
                    'deadline' => $_POST['deadline'],
                    'skills_required' => $_POST['skills_required'],
                    'status' => 'open',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $db->insert('tts_gigs', $gigData);
                $message = 'Gig added successfully!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

$gigs = $db->fetchAll("SELECT * FROM tts_gigs ORDER BY created_at DESC");
$stats = [
    'total_gigs' => $db->count('tts_gigs'),
    'open_gigs' => $db->count('tts_gigs', "status = 'open'"),
    'in_progress_gigs' => $db->count('tts_gigs', "status = 'in_progress'"),
    'completed_gigs' => $db->count('tts_gigs', "status = 'completed'")
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gig Management - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #1266f1, #39c0ed); min-height: 100vh; width: 250px; position: fixed; }
        .main-content { margin-left: 250px; padding: 20px; }
        .gig-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        @media (max-width: 768px) { .sidebar { margin-left: -250px; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-4">
            <h4 class="text-white mb-4"><i class="fas fa-shield-alt me-2"></i>TTS Admin</h4>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link text-white mb-2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="applications.php" class="nav-link text-white mb-2"><i class="fas fa-file-alt me-2"></i>Applications</a>
                <a href="employees.php" class="nav-link text-white mb-2"><i class="fas fa-users me-2"></i>Employees</a>
                <a href="payroll-automation.php" class="nav-link text-white mb-2"><i class="fas fa-money-bill-wave me-2"></i>Payroll</a>
                <a href="leaves.php" class="nav-link text-white mb-2"><i class="fas fa-calendar-times me-2"></i>Leaves</a>
                <a href="clients.php" class="nav-link text-white mb-2"><i class="fas fa-handshake me-2"></i>Clients</a>
                <a href="proposals.php" class="nav-link text-white mb-2"><i class="fas fa-file-contract me-2"></i>Proposals</a>
                <a href="gigs.php" class="nav-link text-white mb-2 active bg-white bg-opacity-20 rounded"><i class="fas fa-briefcase me-2"></i>Gigs</a>
                <a href="reports.php" class="nav-link text-white mb-2"><i class="fas fa-chart-bar me-2"></i>Reports</a>
                <a href="settings.php" class="nav-link text-white mb-2"><i class="fas fa-cog me-2"></i>Settings</a>
                <hr class="text-white">
                <a href="logout.php" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </nav>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Gig Management</h2>
                <p class="text-muted mb-0">Manage freelance gigs and projects</p>
            </div>
            <button class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#addGigModal">
                <i class="fas fa-plus me-2"></i>Add Gig
            </button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-mdb-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-briefcase fa-2x text-primary mb-3"></i>
                        <h3><?php echo $stats['total_gigs']; ?></h3>
                        <p class="text-muted mb-0">Total Gigs</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-door-open fa-2x text-success mb-3"></i>
                        <h3><?php echo $stats['open_gigs']; ?></h3>
                        <p class="text-muted mb-0">Open</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-spinner fa-2x text-warning mb-3"></i>
                        <h3><?php echo $stats['in_progress_gigs']; ?></h3>
                        <p class="text-muted mb-0">In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x text-info mb-3"></i>
                        <h3><?php echo $stats['completed_gigs']; ?></h3>
                        <p class="text-muted mb-0">Completed</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($gigs as $gig): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card gig-card">
                    <div class="card-body">
                        <h6 class="mb-2"><?php echo htmlspecialchars($gig['title']); ?></h6>
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars(substr($gig['description'], 0, 100)) . '...'; ?></p>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">Budget:</small>
                            <strong>PKR <?php echo number_format($gig['budget']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted">Deadline:</small>
                            <span><?php echo date('M d, Y', strtotime($gig['deadline'])); ?></span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Skills:</small>
                            <span class="small"><?php echo htmlspecialchars($gig['skills_required']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $gig['status'] === 'open' ? 'success' : ($gig['status'] === 'in_progress' ? 'warning' : 'info'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $gig['status'])); ?>
                            </span>
                            <?php if ($gig['status'] === 'open'): ?>
                            <button class="btn btn-primary btn-sm" onclick="buyGig(<?php echo $gig['id']; ?>, '<?php echo htmlspecialchars($gig['title']); ?>', <?php echo $gig['budget']; ?>)">
                                <i class="fas fa-credit-card me-1"></i>Buy Now
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="addGigModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Gig</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_gig">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Budget (PKR) *</label>
                                <input type="number" class="form-control" name="budget" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deadline *</label>
                                <input type="date" class="form-control" name="deadline" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Skills Required</label>
                            <input type="text" class="form-control" name="skills_required" placeholder="e.g., PHP, JavaScript, MySQL">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Gig</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Stripe Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Payment</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="payment-element">
                        <!-- Stripe Elements will create form elements here -->
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between">
                            <span>Gig:</span>
                            <span id="gigTitle"></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Amount:</span>
                            <strong id="gigAmount"></strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit-payment">
                        <i class="fas fa-lock me-2"></i>Pay Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    <script>
        document.querySelector('input[name="deadline"]').min = new Date().toISOString().split('T')[0];
        
        // Stripe configuration
        const stripe = Stripe('pk_test_51234567890abcdef'); // Replace with your Stripe publishable key
        let elements;
        let currentGigId;
        
        function buyGig(gigId, gigTitle, gigAmount) {
            currentGigId = gigId;
            document.getElementById('gigTitle').textContent = gigTitle;
            document.getElementById('gigAmount').textContent = 'PKR ' + new Intl.NumberFormat().format(gigAmount);
            
            // Initialize Stripe Elements
            initializeStripe(gigAmount);
            
            const modal = new mdb.Modal(document.getElementById('paymentModal'));
            modal.show();
        }
        
        async function initializeStripe(amount) {
            const response = await fetch('../api/create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    amount: amount * 100, // Convert to cents
                    currency: 'pkr',
                    gig_id: currentGigId
                })
            });
            
            const { client_secret } = await response.json();
            
            elements = stripe.elements({ clientSecret: client_secret });
            
            const paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');
        }
        
        document.getElementById('submit-payment').addEventListener('click', async () => {
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.origin + '/admin/payment-success.php?gig_id=' + currentGigId
                }
            });
            
            if (error) {
                alert('Payment failed: ' + error.message);
            }
        });
    </script>
</body>
</html>
