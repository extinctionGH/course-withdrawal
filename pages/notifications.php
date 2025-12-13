<?php
require '../includes/auth.php';
require '../config/db_connect.php';
require '../includes/Notification.php';
include '../includes/header.php';

$db = new Database();
$conn = $db->connect();
$notif = new Notification($conn);

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notif->markAsRead($_GET['id'], $_SESSION['userID']);
    header("Location: notifications.php");
    exit();
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $notif->markAllAsRead($_SESSION['userID']);
    header("Location: notifications.php");
    exit();
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $notif->delete($_GET['id'], $_SESSION['userID']);
    header("Location: notifications.php");
    exit();
}

// Get all notifications
$notifications = $notif->getAll($_SESSION['userID'], 50);
$unreadCount = $notif->getUnreadCount($_SESSION['userID']);
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><i class="fas fa-bell"></i> Notifications</h2>
        <?php if ($unreadCount > 0): ?>
            <a href="?mark_all_read=1" class="btn btn-primary">
                <i class="fas fa-check-double"></i> Mark All as Read
            </a>
        <?php endif; ?>
    </div>

    <?php if (count($notifications) > 0): ?>
        <div class="notifications-list">
            <?php foreach ($notifications as $n): ?>
                <div class="notification-item <?= $n['IsRead'] ? 'read' : 'unread' ?> notification-<?= $n['Type'] ?>">
                    <div class="notification-icon">
                        <?php
                        $icon = 'fa-info-circle';
                        if ($n['Type'] == 'success') $icon = 'fa-check-circle';
                        if ($n['Type'] == 'warning') $icon = 'fa-exclamation-triangle';
                        if ($n['Type'] == 'error') $icon = 'fa-times-circle';
                        ?>
                        <i class="fas <?= $icon ?>"></i>
                    </div>
                    <div class="notification-content">
                        <h4><?= htmlspecialchars($n['Title']) ?></h4>
                        <p><?= htmlspecialchars($n['Message']) ?></p>
                        <small><i class="fas fa-clock"></i> <?= date('M d, Y h:i A', strtotime($n['CreatedAt'])) ?></small>
                    </div>
                    <div class="notification-actions">
                        <?php if (!$n['IsRead']): ?>
                            <a href="?mark_read=1&id=<?= $n['NotificationID'] ?>" class="btn-icon" title="Mark as read">
                                <i class="fas fa-check"></i>
                            </a>
                        <?php endif; ?>
                        <a href="?delete=1&id=<?= $n['NotificationID'] ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this notification?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-bell-slash" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No notifications yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>