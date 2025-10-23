<?php
/**
 * TTS PMS - Email Configuration
 * PHPMailer SMTP settings and email functions
 */

// Prevent direct access
if (!defined('TTS_PMS_INIT')) {
    die('Direct access not allowed');
}

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'tts.workhub@gmail.com');
define('SMTP_PASSWORD', 'wcjr uqat kqlz npvd');
define('FROM_EMAIL', 'tts.workhub@gmail.com');
define('FROM_NAME', 'TTS WorkHub');
define('REPLY_TO_EMAIL', 'tts.workhub@gmail.com');

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Try to include PHPMailer from common locations
        $phpmailer_paths = [
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../includes/PHPMailer/src/PHPMailer.php',
            __DIR__ . '/../includes/PHPMailer/src/SMTP.php',
            __DIR__ . '/../includes/PHPMailer/src/Exception.php'
        ];
        
        // Try composer autoload first
        if (file_exists($phpmailer_paths[0])) {
            require_once $phpmailer_paths[0];
        } else {
            // Manual include
            for ($i = 1; $i < count($phpmailer_paths); $i++) {
                if (file_exists($phpmailer_paths[$i])) {
                    require_once $phpmailer_paths[$i];
                }
            }
        }
    }
    
    // If still not available, use simple fallback
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendEmailFallback($to, $subject, $body);
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(REPLY_TO_EMAIL, FROM_NAME);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        if (!$isHTML) {
            $mail->AltBody = $body;
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Fallback email function using PHP's mail()
 */
function sendEmailFallback($to, $subject, $body) {
    $headers = [
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . REPLY_TO_EMAIL,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Generate email verification token
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Send email verification email
 */
function sendVerificationEmail($email, $token, $firstName = '') {
    $verificationUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . 
                      '://' . $_SERVER['HTTP_HOST'] . 
                      dirname($_SERVER['PHP_SELF']) . 
                      '/verify-email.php?token=' . $token;
    
    $subject = 'Verify Your Email - TTS WorkHub';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Verification</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 Welcome to TTS WorkHub!</h1>
                <p>Precision Data, Global Talent</p>
            </div>
            <div class="content">
                <h2>Hello ' . htmlspecialchars($firstName ?: 'there') . '!</h2>
                <p>Thank you for registering with TTS WorkHub. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                
                <div style="text-align: center;">
                    <a href="' . $verificationUrl . '" class="button">Verify Email Address</a>
                </div>
                
                <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
                <p style="background: #e9ecef; padding: 10px; border-radius: 5px; word-break: break-all;">
                    ' . $verificationUrl . '
                </p>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This verification link will expire in 24 hours</li>
                    <li>You must verify your email before you can sign in</li>
                    <li>If you didn\'t create this account, please ignore this email</li>
                </ul>
                
                <p>Once verified, you can sign in and start your journey with TTS WorkHub!</p>
                
                <p>Best regards,<br>
                <strong>TTS WorkHub Team</strong></p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Tech & Talent Solutions. All rights reserved.</p>
                <p>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($email, $subject, $body, true);
}

/**
 * Send welcome email after successful verification
 */
function sendWelcomeEmail($email, $firstName = '') {
    $subject = 'Welcome to TTS WorkHub - Account Activated!';
    
    $loginUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . 
               '://' . $_SERVER['HTTP_HOST'] . 
               dirname($_SERVER['PHP_SELF']) . 
               '/sign-in.php';
    
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to TTS WorkHub</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .feature { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎊 Account Activated!</h1>
                <p>Welcome to TTS WorkHub</p>
            </div>
            <div class="content">
                <h2>Congratulations ' . htmlspecialchars($firstName ?: '') . '!</h2>
                <p>Your email has been successfully verified and your TTS WorkHub account is now active!</p>
                
                <div style="text-align: center;">
                    <a href="' . $loginUrl . '" class="button">Sign In to Your Account</a>
                </div>
                
                <h3>What\'s Next?</h3>
                <div class="feature">
                    <h4>📝 Complete Your Profile</h4>
                    <p>Fill out your profile information to get started with work opportunities.</p>
                </div>
                
                <div class="feature">
                    <h4>📋 Take the Evaluation</h4>
                    <p>Complete our skills evaluation to determine your eligibility for projects.</p>
                </div>
                
                <div class="feature">
                    <h4>💼 Start Working</h4>
                    <p>Once approved, you can start taking on data entry projects and earning!</p>
                </div>
                
                <p>If you have any questions or need assistance, feel free to contact our support team.</p>
                
                <p>Welcome aboard!<br>
                <strong>TTS WorkHub Team</strong></p>
            </div>
        </div>
    </body>
    </html>';
    
    return sendEmail($email, $subject, $body, true);
}

?>
