<?php
require '../includes/auth.php';
require '../config/db_connect.php';
require '../includes/header.php';

if (strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

// Only show requests directed to THIS teacher
$stmt = $conn->prepare("
    SELECT wr.RequestID, u.FullName, c.CourseName, wr.RequestDate, wr.Reason, wr.Status
    FROM withdrawal_request wr
    JOIN user u ON wr.UserID = u.UserID
    JOIN course c ON wr.CourseID = c.CourseID
    WHERE wr.TeacherID = :teacherID
    ORDER BY wr.RequestDate DESC
");
$stmt->execute([':teacherID' => $_SESSION['userID']]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h2>Review Withdrawal Requests</h2>
    
    <?php if ($requests): ?>
    <table class="styled-table">
        <tr>
            <th>Student</th>
            <th>Course</th>
            <th>Reason</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php foreach ($requests as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['FullName']) ?></td>
                <td><?= htmlspecialchars($r['CourseName']) ?></td>
                <td><?= htmlspecialchars($r['Reason']) ?></td>
                <td><?= htmlspecialchars($r['RequestDate']) ?></td>
                <td class="<?= strtolower($r['Status']) ?>"><?= htmlspecialchars($r['Status']) ?></td>
                <td>
                    <?php if ($r['Status'] === 'Pending'): ?>
                        <form method="POST" action="update_status.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $r['RequestID'] ?>">
                            <button name="status" value="Approved" class="btn btn-success">Approve</button>
                            <button name="status" value="Rejected" class="btn btn-danger">Reject</button>
                        </form>
                    <?php else: ?>
                        <em><?= $r['Status'] ?></em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p>No requests sent to you.</p>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>