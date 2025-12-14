<?php
/**
 * Email Configuration
 * 
 * ⚠️ SECURITY WARNING FOR PRODUCTION:
 * Before deploying to Hostinger, update these credentials:
 * 1. Create a new email account in Hostinger hPanel
 * 2. Update SMTP_HOST to Hostinger's SMTP server (usually smtp.hostinger.com)
 * 3. Update SMTP_USERNAME and SMTP_FROM_EMAIL to your Hostinger email
 * 4. Generate a new password and update SMTP_PASSWORD
 * 5. NEVER commit real credentials to Git repositories
 */

// SMTP Server Settings - UPDATE FOR PRODUCTION
define('SMTP_HOST', 'smtp.gmail.com');  // Your SMTP host
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL

// Email Credentials - UPDATE FOR PRODUCTION
define('SMTP_USERNAME', 'your_email@gmail.com');
// IMPORTANT: Use App Password, not regular Gmail password
// Generate at: https://myaccount.google.com/apppasswords
define('SMTP_PASSWORD', 'your_app_password'); // Your App Password

// Sender Information
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');
define('SMTP_FROM_NAME', 'Course Withdrawal System');

// Email sending enabled flag
define('EMAIL_ENABLED', true); // Set to false to disable emails during testing
?>