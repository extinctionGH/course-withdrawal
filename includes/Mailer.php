<?php
require_once __DIR__ . '/../config/email_config.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If using Composer
require __DIR__ . '/../vendor/autoload.php';

// If downloaded manually
// require __DIR__ . '/PHPMailer/src/Exception.php';
// require __DIR__ . '/PHPMailer/src/PHPMailer.php';
// require __DIR__ . '/PHPMailer/src/SMTP.php';

class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = SMTP_USERNAME;
            $this->mail->Password = SMTP_PASSWORD;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = SMTP_PORT;

            // Sender
            $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $this->mail->isHTML(true);

        } catch (Exception $e) {
            error_log("Mailer Error: {$e->getMessage()}");
        }
    }

    public function sendRequestSubmittedEmail($studentEmail, $studentName, $courseName, $teacherName)
    {
        try {
            $this->mail->addAddress($studentEmail, $studentName);
            $this->mail->Subject = 'Withdrawal Request Submitted';

            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #5bc0be 0%, #3a506b 100%); padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>Course Withdrawal System</h1>
                </div>
                <div style='padding: 30px; background: #f5f5f5;'>
                    <h2 style='color: #1c2541;'>Request Submitted Successfully</h2>
                    <p>Dear {$studentName},</p>
                    <p>Your withdrawal request has been submitted successfully.</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <p><strong>Course:</strong> {$courseName}</p>
                        <p><strong>Teacher:</strong> {$teacherName}</p>
                        <p><strong>Status:</strong> <span style='color: #ffd93d;'>Pending</span></p>
                    </div>
                    <p>You will receive an email notification once your request has been reviewed.</p>
                </div>
                <div style='background: #1c2541; padding: 15px; text-align: center; color: #9ad1d4;'>
                    <small>&copy; " . date('Y') . " Course Withdrawal System</small>
                </div>
            </div>
            ";

            $this->mail->Body = $body;
            $this->mail->send();
            $this->mail->clearAddresses();
            return true;

        } catch (Exception $e) {
            error_log("Email Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    public function sendRequestReviewedEmail($studentEmail, $studentName, $courseName, $status, $adminRemarks = '')
    {
        try {
            $this->mail->addAddress($studentEmail, $studentName);
            $this->mail->Subject = "Withdrawal Request {$status}";

            $statusColor = $status == 'Approved' ? '#6bcf7f' : '#ff7b7b';

            // Build remarks section if provided
            $remarksSection = '';
            if (!empty($adminRemarks)) {
                $remarksSection = "
                        <p><strong>Admin Remarks:</strong></p>
                        <p style='background: #f0f0f0; padding: 15px; border-radius: 5px; border-left: 4px solid {$statusColor}; margin: 10px 0;'>" . htmlspecialchars($adminRemarks) . "</p>
                ";
            }

            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #5bc0be 0%, #3a506b 100%); padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>Course Withdrawal System</h1>
                </div>
                <div style='padding: 30px; background: #f5f5f5;'>
                    <h2 style='color: #1c2541;'>Request {$status}</h2>
                    <p>Dear {$studentName},</p>
                    <p>Your withdrawal request has been reviewed.</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <p><strong>Course:</strong> {$courseName}</p>
                        <p><strong>Status:</strong> <span style='color: {$statusColor}; font-weight: bold;'>{$status}</span></p>
                        {$remarksSection}
                    </div>
                    <p>Please login to your account to view more details.</p>
                    <a href='http://localhost/course-withdrawal-system/auth/login.php' style='display: inline-block; background: #5bc0be; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>View Dashboard</a>
                </div>
                <div style='background: #1c2541; padding: 15px; text-align: center; color: #9ad1d4;'>
                    <small>&copy; " . date('Y') . " Course Withdrawal System</small>
                </div>
            </div>
            ";

            $this->mail->Body = $body;
            $this->mail->send();
            $this->mail->clearAddresses();
            return true;

        } catch (Exception $e) {
            error_log("Email Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    public function sendNewRequestNotification($teacherEmail, $teacherName, $studentName, $courseName)
    {
        try {
            $this->mail->addAddress($teacherEmail, $teacherName);
            $this->mail->Subject = 'New Withdrawal Request';

            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #5bc0be 0%, #3a506b 100%); padding: 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>Course Withdrawal System</h1>
                </div>
                <div style='padding: 30px; background: #f5f5f5;'>
                    <h2 style='color: #1c2541;'>New Withdrawal Request</h2>
                    <p>Dear {$teacherName},</p>
                    <p>You have received a new withdrawal request that requires your review.</p>
                    <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <p><strong>Student:</strong> {$studentName}</p>
                        <p><strong>Course:</strong> {$courseName}</p>
                        <p><strong>Status:</strong> <span style='color: #ffd93d;'>Pending Review</span></p>
                    </div>
                    <p>Please login to review and respond to this request.</p>
                    <a href='http://localhost/course-withdrawal-system/auth/login.php' style='display: inline-block; background: #5bc0be; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Review Request</a>
                </div>
                <div style='background: #1c2541; padding: 15px; text-align: center; color: #9ad1d4;'>
                    <small>&copy; " . date('Y') . " Course Withdrawal System</small>
                </div>
            </div>
            ";

            $this->mail->Body = $body;
            $this->mail->send();
            $this->mail->clearAddresses();
            return true;

        } catch (Exception $e) {
            error_log("Email Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}
?>