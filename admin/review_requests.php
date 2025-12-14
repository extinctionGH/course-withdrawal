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

// Get search and filter parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : 'all';

// Build base query
$query = "
    SELECT wr.RequestID, u.FullName, c.CourseName, wr.RequestDate, wr.Reason, wr.Status
    FROM withdrawal_request wr
    JOIN user u ON wr.UserID = u.UserID
    JOIN course c ON wr.CourseID = c.CourseID
    WHERE wr.TeacherID = :teacherID
";

$params = [':teacherID' => $_SESSION['userID']];

// Apply search filter
if (!empty($searchTerm)) {
    $query .= " AND (u.FullName LIKE :search OR c.CourseName LIKE :search OR wr.Reason LIKE :search)";
    $params[':search'] = "%{$searchTerm}%";
}

// Apply status filter
if ($statusFilter !== 'all') {
    $query .= " AND wr.Status = :status";
    $params[':status'] = $statusFilter;
}

// Apply date filter
if ($dateFilter !== 'all') {
    switch ($dateFilter) {
        case 'today':
            $query .= " AND DATE(wr.RequestDate) = CURDATE()";
            break;
        case 'week':
            $query .= " AND wr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $query .= " AND wr.RequestDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
}

$query .= " ORDER BY wr.RequestDate DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics for the current filters
$totalCount = count($requests);
$pendingCount = count(array_filter($requests, fn($r) => $r['Status'] === 'Pending'));
?>

<div class="card">
    <h2><i class="fas fa-tasks"></i> Review Withdrawal Requests</h2>
    
    <!-- Search and Filter Section -->
    <div style="background: #0f1a35; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
        <form method="GET" id="searchForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="grid-column: 1 / -1;">
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search
                </label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by student, course, or reason..." 
                       value="<?= htmlspecialchars($searchTerm) ?>"
                       style="width: 100%;">
            </div>
            
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-filter"></i> Status
                </label>
                <select name="status" style="width: 100%;">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-calendar"></i> Date
                </label>
                <select name="date" style="width: 100%;">
                    <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: flex-end;">
                <button type="submit" class="btn">Filter</button>
                <a href="review_requests.php" class="btn" style="background: #3a506b;">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Results Summary -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: rgba(91, 192, 190, 0.1); border-radius: 8px; border-left: 4px solid #5bc0be;">
        <div>
            <strong style="color: #5bc0be; font-size: 16px;">
                <i class="fas fa-list"></i> Found <?= $totalCount ?> request<?= $totalCount != 1 ? 's' : '' ?>
            </strong>
            <?php if (!empty($searchTerm)): ?>
                <span style="color: #9ad1d4; margin-left: 10px;">
                    for "<?= htmlspecialchars($searchTerm) ?>"
                </span>
            <?php endif; ?>
        </div>
        <?php if ($pendingCount > 0): ?>
            <div style="background: rgba(255, 217, 61, 0.15); padding: 8px 16px; border-radius: 6px; border: 1px solid #ffd93d;">
                <i class="fas fa-exclamation-circle" style="color: #ffd93d;"></i>
                <strong style="color: #ffd93d;"><?= $pendingCount ?></strong> pending review
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Requests Table -->
    <?php if ($requests): ?>
    <div style="overflow-x: auto;">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td>
                            <i class="fas fa-user-graduate" style="color: #5bc0be; margin-right: 5px;"></i>
                            <?= htmlspecialchars($r['FullName']) ?>
                        </td>
                        <td><?= htmlspecialchars($r['CourseName']) ?></td>
                        <td>
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($r['Reason']) ?>
                            </div>
                        </td>
                        <td>
                            <i class="fas fa-calendar-alt" style="color: #9ad1d4; margin-right: 5px;"></i>
                            <?= date('M d, Y', strtotime($r['RequestDate'])) ?>
                        </td>
                        <td class="<?= strtolower($r['Status']) ?>"><?= htmlspecialchars($r['Status']) ?></td>
                        <td>
                            <?php if ($r['Status'] === 'Pending'): ?>
                                <form method="POST" action="update_status.php" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $r['RequestID'] ?>">
                                    <button name="status" value="Approved" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button name="status" value="Rejected" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #6b7b8c; font-style: italic;">
                                    <i class="fas fa-check-circle"></i> <?= $r['Status'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No requests found matching your criteria.</p>
            <?php if (!empty($searchTerm) || $statusFilter !== 'all' || $dateFilter !== 'all'): ?>
                <a href="review_requests.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Auto-submit form on filter change
document.querySelectorAll('#searchForm select').forEach(select => {
    select.addEventListener('change', function() {
        document.getElementById('searchForm').submit();
    });
});

// Highlight search terms in results
<?php if (!empty($searchTerm)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = <?= json_encode($searchTerm) ?>;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    
    document.querySelectorAll('table tbody td').forEach(cell => {
        if (cell.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
            cell.innerHTML = cell.innerHTML.replace(regex, '<mark style="background: rgba(91, 192, 190, 0.3); padding: 2px 4px; border-radius: 3px;">$1</mark>');
        }
    });
});
<?php endif; ?>
</script>

<?php require '../includes/footer.php'; ?>