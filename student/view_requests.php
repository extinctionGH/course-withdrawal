<?php
require '../includes/auth.php';
require '../config/db_connect.php';
include '../includes/header.php';

$db = new Database();
$conn = $db->connect();

// Get search and filter parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : 'all';

// Build query
$query = "
    SELECT wr.RequestID, c.CourseName, t.FullName as TeacherName, s.SectionName,
           wr.RequestDate, wr.Reason, wr.Status
    FROM withdrawal_request wr
    JOIN course c ON wr.CourseID = c.CourseID
    JOIN user t ON wr.TeacherID = t.UserID
    LEFT JOIN section s ON wr.SectionID = s.SectionID
    WHERE wr.UserID = :userid
";

$params = [':userid' => $_SESSION['userID']];

// Apply search
if (!empty($searchTerm)) {
    $query .= " AND (c.CourseName LIKE :search OR t.FullName LIKE :search OR wr.Reason LIKE :search)";
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

// Calculate statistics
$totalCount = count($requests);
$pendingCount = count(array_filter($requests, fn($r) => $r['Status'] === 'Pending'));
$approvedCount = count(array_filter($requests, fn($r) => $r['Status'] === 'Approved'));
$rejectedCount = count(array_filter($requests, fn($r) => $r['Status'] === 'Rejected'));
?>

<div class="card">
    <h2><i class="fas fa-list"></i> My Withdrawal Requests</h2>
    
    <!-- Search and Filter Section -->
    <div style="background: #0f1a35; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
        <form method="GET" id="searchForm">
            <div style="display: grid; grid-template-columns: 1fr 200px 200px auto; gap: 15px; align-items: end;">
                <!-- Search Input -->
                <div>
                    <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                        <i class="fas fa-search"></i> Search
                    </label>
                    <input type="text" 
                           name="search" 
                           placeholder="Search by course, teacher, or reason..." 
                           value="<?= htmlspecialchars($searchTerm) ?>"
                           style="width: 100%;">
                </div>
                
                <!-- Status Filter -->
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
                
                <!-- Date Filter -->
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
                
                <!-- Buttons -->
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="view_requests.php" class="btn" style="background: #3a506b;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Statistics Summary -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: #0f1a35; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #5bc0be;">
            <div style="font-size: 24px; color: #5bc0be; font-weight: bold;"><?= $totalCount ?></div>
            <div style="color: #9ad1d4; font-size: 12px;">Total Found</div>
        </div>
        <div style="background: #0f1a35; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #ffd93d;">
            <div style="font-size: 24px; color: #ffd93d; font-weight: bold;"><?= $pendingCount ?></div>
            <div style="color: #9ad1d4; font-size: 12px;">Pending</div>
        </div>
        <div style="background: #0f1a35; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #6bcf7f;">
            <div style="font-size: 24px; color: #6bcf7f; font-weight: bold;"><?= $approvedCount ?></div>
            <div style="color: #9ad1d4; font-size: 12px;">Approved</div>
        </div>
        <div style="background: #0f1a35; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #ff7b7b;">
            <div style="font-size: 24px; color: #ff7b7b; font-weight: bold;"><?= $rejectedCount ?></div>
            <div style="color: #9ad1d4; font-size: 12px;">Rejected</div>
        </div>
    </div>
    
    <!-- Results Table -->
    <?php if ($requests): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Teacher</th>
                        <th>Section</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <i class="fas fa-book" style="color: #5bc0be; margin-right: 5px;"></i>
                                <?= htmlspecialchars($r['CourseName']) ?>
                            </td>
                            <td>
                                <i class="fas fa-user-tie" style="color: #9ad1d4; margin-right: 5px;"></i>
                                <?= htmlspecialchars($r['TeacherName']) ?>
                            </td>
                            <td><?= htmlspecialchars($r['SectionName'] ?? 'N/A') ?></td>
                            <td>
                                <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($r['Reason']) ?>">
                                    <?= htmlspecialchars($r['Reason']) ?>
                                </div>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt" style="color: #9ad1d4; margin-right: 5px;"></i>
                                <?= date('M d, Y', strtotime($r['RequestDate'])) ?>
                            </td>
                            <td class="<?= strtolower($r['Status']) ?>">
                                <?= htmlspecialchars($r['Status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No requests found<?= !empty($searchTerm) ? ' matching your search' : '' ?>.</p>
            <?php if (!empty($searchTerm) || $statusFilter !== 'all' || $dateFilter !== 'all'): ?>
                <a href="view_requests.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            <?php else: ?>
                <a href="request_form.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Submit Your First Request
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
</script>

<?php include '../includes/footer.php'; ?>