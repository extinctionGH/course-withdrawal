<?php
require '../includes/auth.php';
require '../config/db_connect.php';
require '../includes/Mailer.php';
require '../includes/Notification.php';

if (strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_POST['id'], $_POST['status'])) {
    $db = new Database();
    $conn = $db->connect();

    // Get admin remarks (optional)
    $adminRemarks = isset($_POST['admin_remarks']) ? trim($_POST['admin_remarks']) : '';

    // Security: Verify that this request was sent to THIS teacher
    $checkStmt = $conn->prepare("
        SELECT wr.*, u.FullName as StudentName, u.Email as StudentEmail, c.CourseName
        FROM withdrawal_request wr
        JOIN user u ON wr.UserID = u.UserID
        JOIN course c ON wr.CourseID = c.CourseID
        WHERE wr.RequestID = :id AND wr.TeacherID = :teacherID
    ");
    $checkStmt->execute([
        ':id' => $_POST['id'],
        ':teacherID' => $_SESSION['userID']
    ]);

    if ($checkStmt->rowCount() > 0) {
        $request = $checkStmt->fetch();

        // Update status and admin remarks
        $stmt = $conn->prepare("UPDATE withdrawal_request SET Status = :status, AdminRemarks = :remarks WHERE RequestID = :id");
        $stmt->execute([
            ':status' => $_POST['status'],
            ':remarks' => $adminRemarks ?: null,
            ':id' => $_POST['id']
        ]);

        // Send email to student
        $mailer = new Mailer();
        $mailer->sendRequestReviewedEmail(
            $request['StudentEmail'],
            $request['StudentName'],
            $request['CourseName'],
            $_POST['status'],
            $adminRemarks
        );

        // Create notification for student
        $notif = new Notification($conn);
        $notificationType = $_POST['status'] == 'Approved' ? 'success' : 'warning';
        $notificationMessage = "Your withdrawal request for {$request['CourseName']} has been {$_POST['status']}.";
        if (!empty($adminRemarks)) {
            $notificationMessage .= " Remarks: " . substr($adminRemarks, 0, 100) . (strlen($adminRemarks) > 100 ? '...' : '');
        }
        $notif->create(
            $request['UserID'],
            "Request {$_POST['status']}",
            $notificationMessage,
            $notificationType
        );
    }
}

header("Location: review_requests.php");
exit();
?>