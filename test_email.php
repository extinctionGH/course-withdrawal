<?php
/**
 * Email Configuration Test Script
 * Place this file in your root directory and access via browser
 * URL: http://localhost/course-withdrawal-system/test_email.php
 */

require 'config/email_config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Configuration Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>📧 Email Configuration Test</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $testEmail = $_POST['test_email'] ?? '';
        
        if (empty($testEmail)) {
            echo '<div class="error">❌ Please enter an email address</div>';
        } else {
            echo '<h2>Running Email Test...</h2>';
            
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->SMTPDebug = 2; // Enable verbose debug output
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USERNAME;
                $mail->Password   = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = SMTP_PORT;
                
                // Additional settings for Gmail
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($testEmail);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Test Email from Course Withdrawal System';
                $mail->Body    = '<h1>Success!</h1><p>Your email configuration is working correctly.</p>';
                $mail->AltBody = 'Success! Your email configuration is working correctly.';
                
                // Capture debug output
                ob_start();
                $mail->send();
                $debug = ob_get_clean();
                
                echo '<div class="success">✅ Email sent successfully!</div>';
                echo '<div class="info"><strong>Debug Output:</strong><pre>' . htmlspecialchars($debug) . '</pre></div>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Email could not be sent. Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
                echo '<div class="info"><strong>Debug Output:</strong><pre>' . htmlspecialchars($debug ?? 'No debug output') . '</pre></div>';
            }
        }
    }
    ?>
    
    <div class="info">
        <h3>📋 Current Configuration:</h3>
        <pre><?php
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP Username: " . SMTP_USERNAME . "\n";
echo "SMTP Password: " . (empty(SMTP_PASSWORD) ? "❌ NOT SET" : "✅ SET (hidden)") . "\n";
echo "From Email: " . SMTP_FROM_EMAIL . "\n";
echo "From Name: " . SMTP_FROM_NAME . "\n";
        ?></pre>
    </div>
    
    <div class="info">
        <h3>⚠️ Important Notes:</h3>
        <ul>
            <li><strong>Gmail App Password Required:</strong> You cannot use your regular Gmail password. You must generate an App Password.</li>
            <li><strong>How to generate App Password:</strong>
                <ol>
                    <li>Go to <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                    <li>Enable 2-Step Verification (if not already enabled)</li>
                    <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                    <li>Select "Mail" and "Other (Custom name)"</li>
                    <li>Name it "Course Withdrawal System"</li>
                    <li>Copy the 16-digit password (no spaces)</li>
                    <li>Paste it in your email_config.php file</li>
                </ol>
            </li>
            <li><strong>Format:</strong> App Password should be 16 characters, lowercase, no spaces (e.g., abcdabcdabcdabcd)</li>
        </ul>
    </div>
    
    <form method="POST">
        <h3>Send Test Email:</h3>
        <input type="email" name="test_email" placeholder="Enter your email address" required style="padding: 10px; width: 300px; margin-right: 10px;">
        <button type="submit" class="btn">Send Test Email</button>
    </form>
    
</body>
</html>