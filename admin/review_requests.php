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
        <form method="GET" id="searchForm"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div style="grid-column: 1 / -1;">
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search
                </label>
                <input type="text" name="search" placeholder="Search by student, course, or reason..."
                    value="<?= htmlspecialchars($searchTerm) ?>" style="width: 100%;">
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
                <a href="review_requests.php" class="btn"
                    style="background: #3a506b; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; line-height: 1; box-sizing: border-box;"><i
                        class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: rgba(91, 192, 190, 0.1); border-radius: 8px; border-left: 4px solid #5bc0be;">
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
            <div
                style="background: rgba(255, 217, 61, 0.15); padding: 8px 16px; border-radius: 6px; border: 1px solid #ffd93d;">
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
                                    <button type="button" class="btn btn-success"
                                        onclick="openModal(<?= $r['RequestID'] ?>, 'Approved', '<?= htmlspecialchars($r['FullName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($r['CourseName'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="button" class="btn btn-danger"
                                        onclick="openModal(<?= $r['RequestID'] ?>, 'Rejected', '<?= htmlspecialchars($r['FullName'], ENT_QUOTES) ?>', '<?= htmlspecialchars($r['CourseName'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
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

<!-- Admin Remarks Modal -->
<div id="remarksModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div
        style="background: #1c2541; padding: 30px; border-radius: 15px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
        <h3 style="margin: 0 0 20px 0; color: #e0e6ed;">
            <i class="fas fa-comment-alt" style="color: #5bc0be;"></i>
            <span id="modalTitle">Review Request</span>
        </h3>

        <div style="background: #0f1a35; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin: 0 0 5px 0; color: #9ad1d4;"><strong>Student:</strong> <span id="modalStudent"></span></p>
            <p style="margin: 0; color: #9ad1d4;"><strong>Course:</strong> <span id="modalCourse"></span></p>
        </div>

        <form id="remarksForm" method="POST" action="update_status.php">
            <input type="hidden" name="id" id="modalRequestId">
            <input type="hidden" name="status" id="modalStatus">

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #9ad1d4;">
                    <i class="fas fa-pencil-alt"></i> Admin Remarks (Optional)
                </label>
                <textarea name="admin_remarks" id="adminRemarks" rows="4"
                    placeholder="Add any remarks or feedback for the student..."
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #3a506b; background: #0f1a35; color: #e0e6ed; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" style="background: #3a506b;" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn" id="modalSubmitBtn">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal functions
    function openModal(requestId, status, studentName, courseName) {
        document.getElementById('modalRequestId').value = requestId;
        document.getElementById('modalStatus').value = status;
        document.getElementById('modalStudent').textContent = studentName;
        document.getElementById('modalCourse').textContent = courseName;
        document.getElementById('adminRemarks').value = '';

        const modal = document.getElementById('remarksModal');
        const submitBtn = document.getElementById('modalSubmitBtn');
        const modalTitle = document.getElementById('modalTitle');

        if (status === 'Approved') {
            modalTitle.innerHTML = '<i class="fas fa-check-circle" style="color: #6bcf7f;"></i> Approve Request';
            submitBtn.className = 'btn btn-success';
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Approve Request';
        } else {
            modalTitle.innerHTML = '<i class="fas fa-times-circle" style="color: #ff7b7b;"></i> Reject Request';
            submitBtn.className = 'btn btn-danger';
            submitBtn.innerHTML = '<i class="fas fa-times"></i> Reject Request';
        }

        modal.style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('remarksModal').style.display = 'none';
    }

    // Close modal on outside click
    document.getElementById('remarksModal').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    // Auto-submit form on filter change
    document.querySelectorAll('#searchForm select').forEach(select => {
        select.addEventListener('change', function () {
            document.getElementById('searchForm').submit();
        });
    });

    // Highlight search terms in results
    <?php if (!empty($searchTerm)): ?>
        document.addEventListener('DOMContentLoaded', function () {
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