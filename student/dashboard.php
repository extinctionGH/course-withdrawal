<?php
require '../includes/auth.php';
require '../config/db_connect.php';
include '../includes/header.php';

$db = new Database();
$conn = $db->connect();
$userID = $_SESSION['userID'];

// KPI 1: Total Requests by this student
$totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM withdrawal_request WHERE UserID = :userID");
$totalStmt->execute([':userID' => $userID]);
$totalRequests = $totalStmt->fetch()['total'];

// KPI 2: Pending
$pendingStmt = $conn->prepare("SELECT COUNT(*) as total FROM withdrawal_request WHERE UserID = :userID AND Status = 'Pending'");
$pendingStmt->execute([':userID' => $userID]);
$pendingRequests = $pendingStmt->fetch()['total'];

// KPI 3: Approved
$approvedStmt = $conn->prepare("SELECT COUNT(*) as total FROM withdrawal_request WHERE UserID = :userID AND Status = 'Approved'");
$approvedStmt->execute([':userID' => $userID]);
$approvedRequests = $approvedStmt->fetch()['total'];

// KPI 4: Rejected
$rejectedStmt = $conn->prepare("SELECT COUNT(*) as total FROM withdrawal_request WHERE UserID = :userID AND Status = 'Rejected'");
$rejectedStmt->execute([':userID' => $userID]);
$rejectedRequests = $rejectedStmt->fetch()['total'];

// Recent requests
$stmt = $conn->prepare("
    SELECT wr.RequestID, c.CourseName, t.FullName as TeacherName, wr.RequestDate, wr.Reason, wr.Status
    FROM withdrawal_request wr
    JOIN course c ON wr.CourseID = c.CourseID
    JOIN user t ON wr.TeacherID = t.UserID
    WHERE wr.UserID = :userid
    ORDER BY wr.RequestDate DESC
    LIMIT 5
");
$stmt->execute([':userid' => $userID]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="welcome-banner">
    <h2>Welcome back, <?= htmlspecialchars($_SESSION['fullName']) ?>!</h2>
    <p>Track your course withdrawal requests and their status.</p>
</div>

<!-- KPI Statistics Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Requests</h3>
        <div class="stat-number"><?= $totalRequests ?></div>
        <div class="stat-label">All time</div>
    </div>
    
    <div class="stat-card">
        <h3>Pending</h3>
        <div class="stat-number"><?= $pendingRequests ?></div>
        <div class="stat-label">Awaiting review</div>
    </div>
    
    <div class="stat-card">
        <h3>Approved</h3>
        <div class="stat-number"><?= $approvedRequests ?></div>
        <div class="stat-label">Accepted</div>
    </div>
    
    <div class="stat-card">
        <h3>Rejected</h3>
        <div class="stat-number"><?= $rejectedRequests ?></div>
        <div class="stat-label">Declined</div>
    </div>
</div>

<div class="card">
    <h2>Your Recent Withdrawal Requests</h2>
    <?php if (count($requests) > 0): ?>
        <table>
            <tr>
                <th>Course</th>
                <th>Teacher</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['CourseName']) ?></td>
                    <td><?= htmlspecialchars($r['TeacherName']) ?></td>
                    <td><?= htmlspecialchars($r['Reason']) ?></td>
                    <td><?= htmlspecialchars($r['RequestDate']) ?></td>
                    <td class="<?= strtolower($r['Status']) ?>"><?= htmlspecialchars($r['Status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <a href="view_requests.php" class="btn">View All Requests</a>
    <?php else: ?>
        <p>No requests yet. <a href="request_form.php">Submit your first request</a></p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>