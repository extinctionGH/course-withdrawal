<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Initialize notification system
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/Notification.php';

$db = new Database();
$conn = $db->connect();
$notif = new Notification($conn);

// Get unread notification count
$unreadCount = 0;
if (isset($_SESSION['userID'])) {
    $unreadCount = $notif->getUnreadCount($_SESSION['userID']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Withdrawal System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<header>
    <div class="header-left">
        <h1><i class="fas fa-graduation-cap"></i> Course Withdrawal System</h1>
    </div>
    <div class="header-right">
        <!-- Notification Bell -->
        <div class="notification-container">
            <a href="../pages/notifications.php" class="notification-bell" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        
        <div class="user-info">
            <span title="<?= htmlspecialchars($_SESSION['role']) ?>">
                <i class="fas fa-user-circle"></i> 
                <?= htmlspecialchars($_SESSION['fullName']) ?>
            </span>
            <a href="../auth/logout.php" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<div class="sidebar">
    <?php if (strtolower($_SESSION['role']) === 'admin'): ?>
        <a href="../admin/dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" title="Dashboard">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="../admin/review_requests.php" class="<?= ($current_page == 'review_requests.php') ? 'active' : '' ?>" title="Review Requests">
            <i class="fas fa-tasks"></i> Review Requests
        </a>
        <a href="../admin/reports.php" class="<?= ($current_page == 'reports.php') ? 'active' : '' ?>" title="Reports">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a href="../admin/manage_courses.php" class="<?= ($current_page == 'manage_courses.php') ? 'active' : '' ?>" title="Manage Courses">
            <i class="fas fa-book"></i> Manage Courses
        </a>
        <a href="../admin/manage_sections.php" class="<?= ($current_page == 'manage_sections.php') ? 'active' : '' ?>" title="Manage Sections">
            <i class="fas fa-layer-group"></i> Manage Sections
        </a>
        <a href="../admin/assign_teachers.php" class="<?= ($current_page == 'assign_teachers.php') ? 'active' : '' ?>" title="Assign Teachers">
            <i class="fas fa-user-cog"></i> Assign Teachers
        </a>
        <a href="../admin/manage_students.php" class="<?= ($current_page == 'manage_students.php') ? 'active' : '' ?>" title="Manage Students">
            <i class="fas fa-user-graduate"></i> Manage Students
        </a>
        <a href="../admin/manage_teachers.php" class="<?= ($current_page == 'manage_teachers.php') ? 'active' : '' ?>" title="Manage Teachers">
            <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
        </a>
    <?php else: ?>
        <a href="../student/dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>" title="Dashboard">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="../student/request_form.php" class="<?= ($current_page == 'request_form.php') ? 'active' : '' ?>" title="Submit Request">
            <i class="fas fa-file-alt"></i> Submit Request
        </a>
        <a href="../student/view_requests.php" class="<?= ($current_page == 'view_requests.php') ? 'active' : '' ?>" title="View Requests">
            <i class="fas fa-list"></i> View Requests
        </a>
    <?php endif; ?>
</div>

<div class="main-content">

<!-- Include Modal System JavaScript -->
<script src="../assets/js/modal-system.js"></script>