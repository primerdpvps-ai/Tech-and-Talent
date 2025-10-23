<?php
/**
 * TTS PMS - Email Test Script
 * Test SMTP configuration and email sending
 */

require_once 'config/init.php';

// Test email configuration
echo "<h2>TTS PMS Email Test</h2>";

// Test 1: Check if email configuration is loaded
echo "<h3>1. Configuration Check</h3>";
if (defined('SMTP_HOST')) {
    echo "✅ SMTP Host: " . SMTP_HOST . "<br>";
    echo "✅ SMTP Port: " . SMTP_PORT . "<br>";
    echo "✅ SMTP Username: " . SMTP_USERNAME . "<br>";
    echo "✅ From Email: " . FROM_EMAIL . "<br>";
} else {
    echo "❌ Email configuration not loaded<br>";
}

// Test 2: Check PHPMailer availability
echo "<h3>2. PHPMailer Check</h3>";
try {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✅ PHPMailer class is available<br>";
    } else {
        echo "⚠️ PHPMailer not found, will use fallback method<br>";
    }
} catch (Exception $e) {
    echo "❌ PHPMailer error: " . $e->getMessage() . "<br>";
}

// Test 3: Send test email (only if email parameter is provided)
if (isset($_GET['email']) && !empty($_GET['email'])) {
    $testEmail = filter_var($_GET['email'], FILTER_VALIDATE_EMAIL);
    
    if ($testEmail) {
        echo "<h3>3. Sending Test Email to: " . htmlspecialchars($testEmail) . "</h3>";
        
        try {
            $subject = "TTS PMS Email Test - " . date('Y-m-d H:i:s');
            $body = "
            <h2>Email Test Successful!</h2>
            <p>This is a test email from TTS PMS system.</p>
            <p><strong>Sent at:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>SMTP Configuration:</strong></p>
            <ul>
                <li>Host: " . SMTP_HOST . "</li>
                <li>Port: " . SMTP_PORT . "</li>
                <li>Encryption: " . SMTP_ENCRYPTION . "</li>
            </ul>
            <p>If you received this email, the SMTP configuration is working correctly!</p>
            ";
            
            $result = sendEmail($testEmail, $subject, $body, true);
            
            if ($result) {
                echo "✅ Test email sent successfully!<br>";
                echo "📧 Check your inbox at: " . htmlspecialchars($testEmail) . "<br>";
            } else {
                echo "❌ Failed to send test email<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Email sending error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Invalid email address provided<br>";
    }
} else {
    echo "<h3>3. Test Email Sending</h3>";
    echo "To test email sending, add ?email=your@gmail.com to the URL<br>";
    echo "Example: <a href='?email=test@gmail.com'>test-email.php?email=test@gmail.com</a><br>";
}

// Test 4: Test verification email generation
echo "<h3>4. Verification Email Test</h3>";
try {
    $token = generateVerificationToken();
    echo "✅ Generated verification token: " . substr($token, 0, 16) . "...<br>";
    echo "✅ Token length: " . strlen($token) . " characters<br>";
} catch (Exception $e) {
    echo "❌ Token generation error: " . $e->getMessage() . "<br>";
}

// Test 5: Database connection for user verification
echo "<h3>5. Database Connection Test</h3>";
try {
    $db = Database::getInstance();
    echo "✅ Database connection successful<br>";
    
    // Check if users table has verification fields
    $result = $db->query("SHOW COLUMNS FROM tts_users LIKE 'verification_token'");
    if ($result && $result->rowCount() > 0) {
        echo "✅ Verification token field exists in users table<br>";
    } else {
        echo "⚠️ Verification token field missing - run database migration<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all tests pass, the email system is ready</li>";
echo "<li>Test registration at: <a href='packages/web/auth/sign-up-new.php'>Sign Up Page</a></li>";
echo "<li>Check email verification at: <a href='packages/web/auth/verify-email.php?token=test'>Verification Page</a></li>";
echo "</ol>";

echo "<p><a href='config/system_check.php'>← Back to System Check</a></p>";
?>
