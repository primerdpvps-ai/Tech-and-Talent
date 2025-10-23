<?php
/**
 * TTS PMS - Test Email API
 * Tests SMTP configuration by sending a test email
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/init.php';
require_once '../../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

try {
    $settings = json_decode($_POST['settings'] ?? '{}', true);
    
    if (empty($settings)) {
        throw new Exception('Email settings required');
    }
    
    // Validate required settings
    $required = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email'];
    foreach ($required as $field) {
        if (empty($settings[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Get admin email for test
    $adminEmail = $_SESSION['email'] ?? $settings['from_email'];
    
    // Test email content
    $subject = 'TTS PMS - SMTP Configuration Test';
    $message = "
    <html>
    <head>
        <title>SMTP Test Email</title>
    </head>
    <body>
        <h2>SMTP Configuration Test</h2>
        <p>This is a test email to verify your SMTP configuration is working correctly.</p>
        
        <h3>Configuration Details:</h3>
        <ul>
            <li><strong>SMTP Host:</strong> {$settings['smtp_host']}</li>
            <li><strong>SMTP Port:</strong> {$settings['smtp_port']}</li>
            <li><strong>Encryption:</strong> " . strtoupper($settings['smtp_encryption'] ?? 'none') . "</li>
            <li><strong>From Email:</strong> {$settings['from_email']}</li>
            <li><strong>From Name:</strong> {$settings['from_name']}</li>
        </ul>
        
        <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Sent By:</strong> " . ($_SESSION['name'] ?? 'Admin') . "</p>
        
        <hr>
        <p><small>This email was sent from the TTS PMS Global Settings panel to test SMTP configuration.</small></p>
    </body>
    </html>
    ";
    
    // Send test email using PHPMailer
    $result = sendTestEmail($settings, $adminEmail, $subject, $message);
    
    if ($result['success']) {
        // Log successful test
        log_admin_action(
            'email_test',
            'settings',
            'email',
            null,
            [
                'recipient' => $adminEmail,
                'smtp_host' => $settings['smtp_host'],
                'smtp_port' => $settings['smtp_port']
            ],
            "SMTP test email sent successfully to $adminEmail"
        );
        
        echo json_encode([
            'success' => true,
            'message' => "Test email sent successfully to $adminEmail"
        ]);
    } else {
        throw new Exception($result['error']);
    }
    
} catch (Exception $e) {
    // Log failed test
    log_admin_action(
        'email_test_failed',
        'settings',
        'email',
        null,
        ['error' => $e->getMessage()],
        "SMTP test failed: " . $e->getMessage()
    );
    
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function sendTestEmail($settings, $recipient, $subject, $message) {
    try {
        // Use PHPMailer if available
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return sendWithPHPMailer($settings, $recipient, $subject, $message);
        }
        
        // Fallback to basic mail() function
        return sendWithMailFunction($settings, $recipient, $subject, $message);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function sendWithPHPMailer($settings, $recipient, $subject, $message) {
    require_once '../../includes/PHPMailer/src/PHPMailer.php';
    require_once '../../includes/PHPMailer/src/SMTP.php';
    require_once '../../includes/PHPMailer/src/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_username'];
        $mail->Password = $settings['smtp_password'];
        $mail->Port = (int)$settings['smtp_port'];
        
        // Encryption
        if ($settings['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($settings['smtp_encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        // Recipients
        $mail->setFrom($settings['from_email'], $settings['from_name'] ?? 'TTS PMS');
        $mail->addAddress($recipient);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

function sendWithMailFunction($settings, $recipient, $subject, $message) {
    // Basic headers for HTML email
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>',
        'Reply-To: ' . $settings['from_email'],
        'X-Mailer: TTS PMS'
    ];
    
    $success = mail($recipient, $subject, $message, implode("\r\n", $headers));
    
    if ($success) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Failed to send email using mail() function'];
    }
}
?>
