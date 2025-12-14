<?php
require '../includes/auth.php';
require '../config/db_connect.php';
require '../includes/header.php';

// Only admin can access
if (strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$message = "";

// Get search parameter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle add teacher/admin
if (isset($_POST['add_teacher'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($fullName) && !empty($email) && !empty($password)) {
        $check = $conn->prepare("SELECT * FROM user WHERE Email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            $message = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO user (FullName, Email, Password, Role) VALUES (:fullName, :email, :password, 'Admin')");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':password' => $password
            ]);
            $message = "Teacher/Admin added successfully!";
        }
    } else {
        $message = "Please fill out all fields.";
    }
}

// Handle edit teacher
if (isset($_POST['edit_teacher'])) {
    $id = intval($_POST['user_id']);
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($fullName) && !empty($email)) {
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE user SET FullName = :fullName, Email = :email, Password = :password WHERE UserID = :id AND Role = 'Admin'");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':password' => $password,
                ':id' => $id
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE user SET FullName = :fullName, Email = :email WHERE UserID = :id AND Role = 'Admin'");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':id' => $id
            ]);
        }
        $message = "Teacher/Admin updated successfully!";
    } else {
        $message = "Please enter valid details.";
    }
}

// Handle delete teacher
if (isset($_POST['delete_teacher'])) {
    $id = intval($_POST['user_id']);
    // Prevent deleting yourself
    if ($id != $_SESSION['userID']) {
        $stmt = $conn->prepare("DELETE FROM user WHERE UserID = :id AND Role = 'Admin'");
        $stmt->execute([':id' => $id]);
        $message = "Teacher/Admin deleted successfully!";
    } else {
        $message = "You cannot delete your own account!";
    }
}

// Build query with search
$query = "SELECT * FROM user WHERE Role = 'Admin'";
$params = [];

if (!empty($searchTerm)) {
    $query .= " AND (FullName LIKE :search OR Email LIKE :search)";
    $params[':search'] = "%{$searchTerm}%";
}

$query .= " ORDER BY FullName ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for display
$totalCount = count($teachers);

// Count teachers with students assigned to them
$teacherStatsStmt = $conn->prepare("
    SELECT TeacherID, COUNT(DISTINCT UserID) as StudentCount
    FROM user
    WHERE Role = 'Student' AND TeacherID IS NOT NULL
    GROUP BY TeacherID
");
$teacherStatsStmt->execute();
$teacherStats = [];
while ($row = $teacherStatsStmt->fetch()) {
    $teacherStats[$row['TeacherID']] = $row['StudentCount'];
}
?>

<?php if($message): ?>
    <div class="alert <?= strpos($message, 'success') !== false ? 'alert-success' : 'alert-error' ?>">
        <i class="fas fa-<?= strpos($message, 'success') !== false ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Add Teacher Form -->
<form method="POST" class="card">
    <h2><i class="fas fa-chalkboard-teacher"></i> Manage Teachers/Admin</h2>
    <h3>Add New Teacher/Admin</h3>
    
    <label>Full Name:</label>
    <input type="text" name="full_name" required placeholder="Enter full name">

    <label>Email:</label>
    <input type="email" name="email" required placeholder="Enter email address">

    <label>Password:</label>
    <input type="password" name="password" required placeholder="Enter password">

    <button type="submit" name="add_teacher" class="btn">
        <i class="fas fa-plus"></i> Add Teacher/Admin
    </button>
</form>

<!-- Teachers Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>All Teachers/Admin (<?= $totalCount ?>)</h3>
    </div>
    
    <!-- Search Section -->
    <div style="background: #0f1a35; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search Teachers/Admin
                </label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by name or email..." 
                       value="<?= htmlspecialchars($searchTerm) ?>"
                       style="width: 100%;">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="manage_teachers.php" class="btn" style="background: #3a506b;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($teachers): ?>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Students</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $t): ?>
                    <tr <?= $t['UserID'] == $_SESSION['userID'] ? 'style="background: rgba(91, 192, 190, 0.05);"' : '' ?>>
                        <form method="POST">
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php if ($t['UserID'] == $_SESSION['userID']): ?>
                                        <span style="background: #5bc0be; color: #0b132b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">YOU</span>
                                    <?php endif; ?>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($t['FullName']) ?>" required>
                                </div>
                            </td>
                            <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($t['Email']) ?>" required>
                            </td>
                            <td>
                                <?php 
                                $studentCount = isset($teacherStats[$t['UserID']]) ? $teacherStats[$t['UserID']] : 0;
                                if ($studentCount > 0): 
                                ?>
                                    <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(91, 192, 190, 0.15); padding: 4px 12px; border-radius: 6px; color: #5bc0be; font-weight: 600;">
                                        <i class="fas fa-users"></i>
                                        <?= $studentCount ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #6b7b8c; font-size: 13px;">
                                        <i class="fas fa-minus"></i> None
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="text" name="password" value="<?= htmlspecialchars($t['Password']) ?>" placeholder="Leave empty to keep current">
                            </td>
                            <td>
                                <input type="hidden" name="user_id" value="<?= $t['UserID'] ?>">
                                <button type="submit" name="edit_teacher" class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($t['UserID'] != $_SESSION['userID']): ?>
                                    <button type="submit" name="delete_teacher" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn" style="background: #6b7b8c; cursor: not-allowed;" disabled title="Cannot delete your own account">
                                        <i class="fas fa-lock"></i> Protected
                                    </button>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary Footer -->
        <div style="margin-top: 20px; padding: 15px; background: #0f1a35; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #9ad1d4;">
                <i class="fas fa-info-circle"></i>
                Showing <?= $totalCount ?> teacher<?= $totalCount != 1 ? 's' : '' ?>/admin<?= $totalCount != 1 ? 's' : '' ?>
                <?php if (!empty($searchTerm)): ?>
                    matching "<?= htmlspecialchars($searchTerm) ?>"
                <?php endif; ?>
            </div>
            <div style="color: #6b7b8c; font-size: 13px;">
                <i class="fas fa-shield-alt"></i> Your account is protected from deletion
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-slash" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No teachers/admins found<?= !empty($searchTerm) ? ' matching your search' : '' ?>.</p>
            <?php if (!empty($searchTerm)): ?>
                <a href="manage_teachers.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-redo"></i> Clear Search
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Highlight search terms in results
<?php if (!empty($searchTerm)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = <?= json_encode($searchTerm) ?>;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    
    document.querySelectorAll('table tbody input[type="text"], table tbody input[type="email"]').forEach(input => {
        if (input.value.toLowerCase().includes(searchTerm.toLowerCase())) {
            input.style.background = 'rgba(91, 192, 190, 0.15)';
            input.style.borderColor = '#5bc0be';
        }
    });
});
<?php endif; ?>

// Prevent accidental form submission on Enter key in search
document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.closest('form').submit();
    }
});
</script>

<?php require '../includes/footer.php'; ?>