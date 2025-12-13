<?php
// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'luisatilanoalfaro@gmail.com');
// IMPORTANT: Use App Password, not regular Gmail password
// Generate at: https://myaccount.google.com/apppasswords
define('SMTP_PASSWORD', 'ffidvmeoozszvdsk'); // Replace with App Password
define('SMTP_FROM_EMAIL', 'luisatilanoalfaro@gmail.com');
define('SMTP_FROM_NAME', 'Course Withdrawal System');

// Email sending enabled flag
define('EMAIL_ENABLED', true); // Set to false to disable emails during testing
?>