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
$message = "";

// Get search and filter parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$sectionFilter = isset($_GET['section']) ? $_GET['section'] : 'all';

// Fetch all sections for filter
$sections = $conn->query("SELECT SectionID, SectionName FROM section ORDER BY SectionName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle add student
if (isset($_POST['add_student'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $sectionID = intval($_POST['section_id']);

    if (!empty($fullName) && !empty($email) && !empty($password) && $sectionID > 0) {
        $check = $conn->prepare("SELECT * FROM user WHERE Email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            $message = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO user (FullName, Email, Password, Role, TeacherID, SectionID) VALUES (:fullName, :email, :password, 'Student', :teacherID, :sectionID)");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':password' => $password,
                ':teacherID' => $_SESSION['userID'],
                ':sectionID' => $sectionID
            ]);
            $message = "Student added successfully!";
        }
    } else {
        $message = "Please fill out all fields.";
    }
}

// Handle edit student
if (isset($_POST['edit_student'])) {
    $id = intval($_POST['user_id']);
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $sectionID = intval($_POST['section_id']);

    if (!empty($fullName) && !empty($email) && $sectionID > 0) {
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE user SET FullName = :fullName, Email = :email, Password = :password, SectionID = :sectionID WHERE UserID = :id AND Role = 'Student' AND TeacherID = :teacherID");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':password' => $password,
                ':sectionID' => $sectionID,
                ':id' => $id,
                ':teacherID' => $_SESSION['userID']
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE user SET FullName = :fullName, Email = :email, SectionID = :sectionID WHERE UserID = :id AND Role = 'Student' AND TeacherID = :teacherID");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':sectionID' => $sectionID,
                ':id' => $id,
                ':teacherID' => $_SESSION['userID']
            ]);
        }
        $message = "Student updated successfully!";
    } else {
        $message = "Please enter valid details.";
    }
}

// Handle delete student
if (isset($_POST['delete_student'])) {
    $id = intval($_POST['user_id']);
    $stmt = $conn->prepare("DELETE FROM user WHERE UserID = :id AND Role = 'Student' AND TeacherID = :teacherID");
    $stmt->execute([
        ':id' => $id,
        ':teacherID' => $_SESSION['userID']
    ]);
    $message = "Student deleted successfully!";
}

// Build query with search and filters
$query = "
    SELECT u.*, s.SectionName 
    FROM user u
    LEFT JOIN section s ON u.SectionID = s.SectionID
    WHERE u.Role = 'Student' AND u.TeacherID = :teacherID
";

$params = [':teacherID' => $_SESSION['userID']];

// Apply search
if (!empty($searchTerm)) {
    $query .= " AND (u.FullName LIKE :search OR u.Email LIKE :search OR s.SectionName LIKE :search)";
    $params[':search'] = "%{$searchTerm}%";
}

// Apply section filter
if ($sectionFilter !== 'all') {
    $query .= " AND u.SectionID = :sectionID";
    $params[':sectionID'] = $sectionFilter;
}

$query .= " ORDER BY u.FullName ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Add Student Form -->
<form method="POST" class="card">
    <h2><i class="fas fa-user-graduate"></i> Manage My Students</h2>
    <h3>Add New Student</h3>
    
    <label>Full Name:</label>
    <input type="text" name="full_name" required placeholder="Enter student name">

    <label>Email:</label>
    <input type="email" name="email" required placeholder="Enter email address">

    <label>Password:</label>
    <input type="password" name="password" required placeholder="Enter password">

    <label>Section:</label>
    <select name="section_id" required>
        <option value="">-- Select Section --</option>
        <?php foreach ($sections as $sec): ?>
            <option value="<?= $sec['SectionID'] ?>"><?= htmlspecialchars($sec['SectionName']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="add_student" class="btn">
        <i class="fas fa-plus"></i> Add Student
    </button>
</form>

<!-- Students Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>My Students (<?= count($students) ?>)</h3>
    </div>
    
    <!-- Search and Filter -->
    <div style="background: #0f1a35; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr 250px; gap: 15px; margin-bottom: 15px;">
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search Students
                </label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by name, email, or section..." 
                       value="<?= htmlspecialchars($searchTerm) ?>"
                       style="width: 100%;">
            </div>
            
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-filter"></i> Section
                </label>
                <select name="section" style="width: 100%;">
                    <option value="all" <?= $sectionFilter === 'all' ? 'selected' : '' ?>>All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['SectionID'] ?>" <?= $sectionFilter == $sec['SectionID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sec['SectionName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="submit" class="btn" onclick="this.closest('.card').querySelector('form').submit()">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="manage_students.php" class="btn" style="background: #3a506b; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-redo"></i> Reset
            </a>
        </div>
    </div>
    
    <?php if ($students): ?>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Section</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <form method="POST">
                            <td>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($s['FullName']) ?>" required>
                            </td>
                            <td>
                                <input type="email" name="email" value="<?= htmlspecialchars($s['Email']) ?>" required>
                            </td>
                            <td>
                                <select name="section_id" required>
                                    <option value="">-- Select Section --</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?= $sec['SectionID'] ?>" <?= ($s['SectionID'] == $sec['SectionID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sec['SectionName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="password" value="<?= htmlspecialchars($s['Password']) ?>" placeholder="Leave empty to keep current">
                            </td>
                            <td>
                                <input type="hidden" name="user_id" value="<?= $s['UserID'] ?>">
                                <button type="submit" name="edit_student" class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="submit" name="delete_student" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-graduate" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No students found<?= !empty($searchTerm) ? ' matching your search' : '' ?>.</p>
            <?php if (!empty($searchTerm) || $sectionFilter !== 'all'): ?>
                <a href="manage_students.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-redo"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>