<?php
/**
 * TTS PMS - Stripe Payment Intent Creation
 * Creates payment intent for Stripe payments
 */

require_once '../config/init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $amount = (int)($input['amount'] ?? 0);
    $currency = $input['currency'] ?? 'pkr';
    $gigId = (int)($input['gig_id'] ?? 0);
    
    if ($amount <= 0) {
        throw new Exception('Invalid amount');
    }
    
    if ($gigId <= 0) {
        throw new Exception('Invalid gig ID');
    }
    
    // Initialize Stripe (you'll need to install Stripe PHP library)
    // For now, we'll simulate the response
    
    // In a real implementation, you would:
    // 1. Include Stripe PHP library
    // 2. Set your secret key
    // 3. Create actual payment intent
    
    /*
    require_once '../vendor/autoload.php';
    \Stripe\Stripe::setApiKey('sk_test_your_secret_key_here');
    
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => $currency,
        'metadata' => [
            'gig_id' => $gigId,
            'integration_check' => 'accept_a_payment'
        ]
    ]);
    
    echo json_encode(['client_secret' => $paymentIntent->client_secret]);
    */
    
    // Simulated response for development
    $simulatedClientSecret = 'pi_' . uniqid() . '_secret_' . uniqid();
    
    // Log the payment attempt
    $db = Database::getInstance();
    $db->insert('tts_payment_intents', [
        'gig_id' => $gigId,
        'amount' => $amount,
        'currency' => $currency,
        'client_secret' => $simulatedClientSecret,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'client_secret' => $simulatedClientSecret,
        'message' => 'Payment intent created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
