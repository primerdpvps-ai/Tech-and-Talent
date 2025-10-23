<?php
/**
 * TTS PMS - Custom Proposal Request API
 * Handles custom proposal requests for specific services
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Load configuration
    require_once '../config/init.php';
    
    // Validate required fields
    $required_fields = ['service_id', 'business_name', 'contact', 'brief'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $errors[] = "Field '{$field}' is required";
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Validation errors', 'errors' => $errors]);
        exit;
    }
    
    // Sanitize input data
    $data = [
        'service_id' => (int)$_POST['service_id'],
        'business_name' => trim($_POST['business_name']),
        'contact' => trim($_POST['contact']),
        'brief' => trim($_POST['brief']),
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Validate service ID
    if ($data['service_id'] < 1 || $data['service_id'] > 6) {
        echo json_encode(['success' => false, 'message' => 'Invalid service selected']);
        exit;
    }
    
    // Get database connection
    $db = Database::getInstance();
    
    // Check if table exists, create if not
    if (!$db->tableExists('tts_proposal_requests')) {
        $createTableSQL = "
            CREATE TABLE tts_proposal_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                service_id INT NOT NULL,
                business_name VARCHAR(255) NOT NULL,
                contact VARCHAR(255) NOT NULL,
                brief TEXT NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent TEXT,
                notes TEXT,
                assigned_to INT,
                proposal_sent_at TIMESTAMP NULL,
                proposal_amount DECIMAL(12,2) NULL,
                INDEX idx_status (status),
                INDEX idx_service_id (service_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->query($createTableSQL);
    }
    
    // Service names mapping
    $services = [
        1 => 'Web Development',
        2 => 'Mobile App Development', 
        3 => 'Digital Marketing',
        4 => 'Data Analytics',
        5 => 'Cloud Solutions',
        6 => 'IT Consulting'
    ];
    
    // Insert proposal request
    $insertId = $db->insert('tts_proposal_requests', $data);
    
    if ($insertId) {
        // Send notification email (if configured)
        try {
            $serviceName = $services[$data['service_id']] ?? 'Unknown Service';
            $emailSubject = "New Custom Proposal Request - {$serviceName}";
            $emailBody = "
                <h2>New Custom Proposal Request</h2>
                <p><strong>Service:</strong> {$serviceName}</p>
                <p><strong>Business Name:</strong> {$data['business_name']}</p>
                <p><strong>Contact:</strong> {$data['contact']}</p>
                <p><strong>Project Brief:</strong></p>
                <p>{$data['brief']}</p>
                <p><strong>Submitted:</strong> {$data['created_at']}</p>
                <p><strong>Request ID:</strong> {$insertId}</p>
                <hr>
                <p><small>Please respond within 24 hours as per company policy.</small></p>
            ";
            
            // You can implement email sending here
            // mail('proposals@tts.com.pk', $emailSubject, $emailBody);
            
        } catch (Exception $e) {
            // Log email error but don't fail the request
            log_message('warning', 'Failed to send proposal notification email: ' . $e->getMessage());
        }
        
        // Log successful submission
        log_message('info', 'Proposal request submitted successfully', [
            'request_id' => $insertId,
            'service_id' => $data['service_id'],
            'business_name' => $data['business_name']
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Proposal request submitted successfully',
            'request_id' => $insertId,
            'service' => $services[$data['service_id']] ?? 'Unknown'
        ]);
        
    } else {
        throw new Exception('Failed to save proposal request');
    }
    
} catch (Exception $e) {
    // Log error
    log_message('error', 'Proposal request submission failed: ' . $e->getMessage(), [
        'post_data' => $_POST,
        'error' => $e->getMessage()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error. Please try again later.'
    ]);
}
?>
