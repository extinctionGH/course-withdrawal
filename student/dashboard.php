<?php
require '../includes/auth.php';
require '../config/db_connect.php';
include '../includes/header.php';

$db = new Database();
$conn = $db->connect();
$userID = $_SESSION['userID'];

// Get student's section info
$studentInfoStmt = $conn->prepare("
    SELECT u.*, s.SectionName 
    FROM user u
    LEFT JOIN section s ON u.SectionID = s.SectionID
    WHERE u.UserID = :id
");
$studentInfoStmt->execute([':id' => $userID]);
$studentInfo = $studentInfoStmt->fetch();

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

// Recent requests with teacher info
$stmt = $conn->prepare("
    SELECT wr.RequestID, c.CourseName, t.FullName as TeacherName, 
           s.SectionName, wr.RequestDate, wr.Reason, wr.Status
    FROM withdrawal_request wr
    JOIN course c ON wr.CourseID = c.CourseID
    JOIN user t ON wr.TeacherID = t.UserID
    LEFT JOIN section s ON wr.SectionID = s.SectionID
    WHERE wr.UserID = :userid
    ORDER BY wr.RequestDate DESC
    LIMIT 5
");
$stmt->execute([':userid' => $userID]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of teachers student has sent requests to
$teachersStmt = $conn->prepare("
    SELECT DISTINCT t.UserID, t.FullName, t.Email,
           COUNT(wr.RequestID) as RequestCount
    FROM withdrawal_request wr
    JOIN user t ON wr.TeacherID = t.UserID
    WHERE wr.UserID = :userid
    GROUP BY t.UserID, t.FullName, t.Email
    ORDER BY RequestCount DESC, t.FullName ASC
");
$teachersStmt->execute([':userid' => $userID]);
$requestedTeachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="welcome-banner">
    <h2>Welcome back, <?= htmlspecialchars($_SESSION['fullName']) ?>!</h2>
    <p>Track your course withdrawal requests and their status.</p>
    <?php if (!empty($studentInfo['SectionName'])): ?>
        <p style="margin-top: 10px;">
            <i class="fas fa-users-class"></i> <strong>Your Section:</strong> 
            <span style="color: #5bc0be; font-weight: 600;"><?= htmlspecialchars($studentInfo['SectionName']) ?></span>
        </p>
    <?php endif; ?>
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

<!-- Teachers You've Sent Requests To -->
<?php if (!empty($requestedTeachers)): ?>
<div class="card">
    <h2><i class="fas fa-chalkboard-teacher"></i> Teachers You've Sent Requests To</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
        <?php foreach ($requestedTeachers as $t): ?>
            <div style="background: #0f1a35; padding: 20px; border-radius: 10px; border-left: 4px solid #5bc0be;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div style="background: rgba(91, 192, 190, 0.2); width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user-tie" style="color: #5bc0be; font-size: 20px;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; color: #e0e6ed; font-size: 16px; font-weight: 600;">
                            <?= htmlspecialchars($t['FullName']) ?>
                        </h3>
                        <small style="color: #9ad1d4; font-size: 13px;">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($t['Email']) ?>
                        </small>
                    </div>
                </div>
                <div style="border-top: 1px solid #243b55; padding-top: 10px; margin-top: 10px;">
                    <span style="color: #9ad1d4; font-size: 13px;">
                        <i class="fas fa-file-alt"></i> <?= $t['RequestCount'] ?> request<?= $t['RequestCount'] != 1 ? 's' : '' ?> sent
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Requests -->
<div class="card">
    <h2><i class="fas fa-history"></i> Your Recent Withdrawal Requests</h2>
    <?php if (count($requests) > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <tr>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Section</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['CourseName']) ?></td>
                        <td>
                            <i class="fas fa-user-tie" style="margin-right: 5px; color: #5bc0be;"></i>
                            <?= htmlspecialchars($r['TeacherName']) ?>
                        </td>
                        <td><?= htmlspecialchars($r['SectionName'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars(substr($r['Reason'], 0, 50)) . (strlen($r['Reason']) > 50 ? '...' : '') ?></td>
                        <td><?= htmlspecialchars($r['RequestDate']) ?></td>
                        <td class="<?= strtolower($r['Status']) ?>"><?= htmlspecialchars($r['Status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <br>
        <a href="view_requests.php" class="btn"><i class="fas fa-list"></i> View All Requests</a>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No requests yet. <a href="request_form.php" style="color: #5bc0be; text-decoration: underline;">Submit your first request</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>