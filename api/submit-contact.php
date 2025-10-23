<?php
/**
 * TTS PMS - Contact Form Submission API
 * Handles contact form submissions from the main landing page
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
    $required_fields = ['business_name', 'contact_person', 'email', 'phone', 'service_type', 'project_brief'];
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
        'business_name' => trim($_POST['business_name']),
        'contact_person' => trim($_POST['contact_person']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'service_type' => (int)$_POST['service_type'],
        'project_brief' => trim($_POST['project_brief']),
        'budget' => isset($_POST['budget']) ? trim($_POST['budget']) : null,
        'status' => 'new',
        'created_at' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Validate phone format (basic Pakistan format)
    if (!preg_match('/^(\+92|0)?[0-9]{10}$/', str_replace([' ', '-', '(', ')'], '', $data['phone']))) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }
    
    // Get database connection
    $db = Database::getInstance();
    
    // Check if table exists, create if not
    if (!$db->tableExists('tts_client_requests')) {
        $createTableSQL = "
            CREATE TABLE tts_client_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                business_name VARCHAR(255) NOT NULL,
                contact_person VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                service_type INT NOT NULL,
                project_brief TEXT NOT NULL,
                budget VARCHAR(50),
                status VARCHAR(50) DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent TEXT,
                notes TEXT,
                assigned_to INT,
                INDEX idx_status (status),
                INDEX idx_created_at (created_at),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->query($createTableSQL);
    }
    
    // Insert contact request
    $insertId = $db->insert('tts_client_requests', $data);
    
    if ($insertId) {
        // Send notification email (if configured)
        try {
            $emailSubject = "New Contact Request from {$data['business_name']}";
            $emailBody = "
                <h2>New Contact Request</h2>
                <p><strong>Business Name:</strong> {$data['business_name']}</p>
                <p><strong>Contact Person:</strong> {$data['contact_person']}</p>
                <p><strong>Email:</strong> {$data['email']}</p>
                <p><strong>Phone:</strong> {$data['phone']}</p>
                <p><strong>Service Type:</strong> {$data['service_type']}</p>
                <p><strong>Budget:</strong> {$data['budget']}</p>
                <p><strong>Project Brief:</strong></p>
                <p>{$data['project_brief']}</p>
                <p><strong>Submitted:</strong> {$data['created_at']}</p>
                <p><strong>Request ID:</strong> {$insertId}</p>
            ";
            
            // You can implement email sending here
            // mail('admin@tts.com.pk', $emailSubject, $emailBody);
            
        } catch (Exception $e) {
            // Log email error but don't fail the request
            log_message('warning', 'Failed to send contact notification email: ' . $e->getMessage());
        }
        
        // Log successful submission
        log_message('info', 'Contact form submitted successfully', [
            'request_id' => $insertId,
            'business_name' => $data['business_name'],
            'email' => $data['email']
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Contact request submitted successfully',
            'request_id' => $insertId
        ]);
        
    } else {
        throw new Exception('Failed to save contact request');
    }
    
} catch (Exception $e) {
    // Log error
    log_message('error', 'Contact form submission failed: ' . $e->getMessage(), [
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
