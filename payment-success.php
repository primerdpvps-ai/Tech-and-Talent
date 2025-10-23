<?php
/**
 * TTS PMS - Payment Success Page
 * Displays payment confirmation
 */

$serviceId = $_GET['service_id'] ?? '';
$paymentIntentId = $_GET['payment_intent'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - TTS PMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h2 class="text-success mb-3">Payment Successful!</h2>
                        <p class="text-muted mb-4">
                            Thank you for your payment. Your transaction has been processed successfully.
                        </p>
                        
                        <?php if ($serviceId): ?>
                        <div class="alert alert-info">
                            <strong>Service ID:</strong> #<?php echo htmlspecialchars($serviceId); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($paymentIntentId): ?>
                        <div class="alert alert-secondary">
                            <strong>Transaction ID:</strong> <?php echo htmlspecialchars($paymentIntentId); ?>
                        </div>
                        <?php endif; ?>
                        
                        <p class="small text-muted mb-4">
                            You will receive a confirmation email shortly with your payment details and next steps.
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Return to Homepage
                            </a>
                            <a href="mailto:info@tts.com.pk" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
