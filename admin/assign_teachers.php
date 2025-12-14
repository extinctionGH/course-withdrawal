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

// Handle add assignment
if (isset($_POST['add_assignment'])) {
    $teacherID = intval($_POST['teacher_id']);
    $courseID = intval($_POST['course_id']);
    $sectionID = intval($_POST['section_id']);

    if ($teacherID > 0 && $courseID > 0 && $sectionID > 0) {
        $check = $conn->prepare("SELECT * FROM teacher_course_section WHERE TeacherID = :tid AND CourseID = :cid AND SectionID = :sid");
        $check->execute([':tid' => $teacherID, ':cid' => $courseID, ':sid' => $sectionID]);
        
        if ($check->rowCount() > 0) {
            $message = "This assignment already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO teacher_course_section (TeacherID, CourseID, SectionID) VALUES (:tid, :cid, :sid)");
            $stmt->execute([':tid' => $teacherID, ':cid' => $courseID, ':sid' => $sectionID]);
            $message = "Assignment created successfully!";
        }
    } else {
        $message = "Please select all fields.";
    }
}

// Handle edit assignment
if (isset($_POST['edit_assignment'])) {
    $id = intval($_POST['assignment_id']);
    $teacherID = intval($_POST['teacher_id']);
    $courseID = intval($_POST['course_id']);
    $sectionID = intval($_POST['section_id']);

    if ($teacherID > 0 && $courseID > 0 && $sectionID > 0) {
        // Check if the new combination already exists (excluding current record)
        $check = $conn->prepare("SELECT * FROM teacher_course_section WHERE TeacherID = :tid AND CourseID = :cid AND SectionID = :sid AND ID != :id");
        $check->execute([':tid' => $teacherID, ':cid' => $courseID, ':sid' => $sectionID, ':id' => $id]);
        
        if ($check->rowCount() > 0) {
            $message = "This assignment already exists!";
        } else {
            $stmt = $conn->prepare("UPDATE teacher_course_section SET TeacherID = :tid, CourseID = :cid, SectionID = :sid WHERE ID = :id");
            $stmt->execute([':tid' => $teacherID, ':cid' => $courseID, ':sid' => $sectionID, ':id' => $id]);
            $message = "Assignment updated successfully!";
        }
    } else {
        $message = "Please select all fields.";
    }
}

// Handle delete assignment
if (isset($_POST['delete_assignment'])) {
    $id = intval($_POST['assignment_id']);
    $stmt = $conn->prepare("DELETE FROM teacher_course_section WHERE ID = :id");
    $stmt->execute([':id' => $id]);
    $message = "Assignment deleted successfully!";
}

// Fetch all teachers
$teachers = $conn->query("SELECT UserID, FullName FROM user WHERE Role = 'Admin' ORDER BY FullName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all courses
$courses = $conn->query("SELECT CourseID, CourseName FROM course ORDER BY CourseName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all sections
$sections = $conn->query("SELECT SectionID, SectionName FROM section ORDER BY SectionName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all assignments with search
$query = "
    SELECT tcs.ID, u.FullName as TeacherName, c.CourseName, s.SectionName, tcs.TeacherID, tcs.CourseID, tcs.SectionID
    FROM teacher_course_section tcs
    JOIN user u ON tcs.TeacherID = u.UserID
    JOIN course c ON tcs.CourseID = c.CourseID
    JOIN section s ON tcs.SectionID = s.SectionID
";

$params = [];

if (!empty($searchTerm)) {
    $query .= " WHERE u.FullName LIKE :search OR c.CourseName LIKE :search OR s.SectionName LIKE :search";
    $params[':search'] = "%{$searchTerm}%";
}

$query .= " ORDER BY u.FullName, c.CourseName, s.SectionName";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCount = count($assignments);
?>

<?php if($message): ?>
    <div class="alert <?= strpos($message, 'success') !== false || strpos($message, 'created') !== false || strpos($message, 'updated') !== false || strpos($message, 'deleted') !== false ? 'alert-success' : 'alert-error' ?>">
        <i class="fas fa-<?= strpos($message, 'success') !== false || strpos($message, 'created') !== false || strpos($message, 'updated') !== false || strpos($message, 'deleted') !== false ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Add Assignment Form -->
<form method="POST" class="card">
    <h2><i class="fas fa-user-cog"></i> Assign Teachers to Course-Section</h2>
    <h3>Create New Assignment</h3>
    
    <label>Teacher/Professor:</label>
    <select name="teacher_id" required>
        <option value="">-- Select Teacher --</option>
        <?php foreach ($teachers as $t): ?>
            <option value="<?= $t['UserID'] ?>"><?= htmlspecialchars($t['FullName']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Course:</label>
    <select name="course_id" required>
        <option value="">-- Select Course --</option>
        <?php foreach ($courses as $c): ?>
            <option value="<?= $c['CourseID'] ?>"><?= htmlspecialchars($c['CourseName']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Section:</label>
    <select name="section_id" required>
        <option value="">-- Select Section --</option>
        <?php foreach ($sections as $s): ?>
            <option value="<?= $s['SectionID'] ?>"><?= htmlspecialchars($s['SectionName']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit" name="add_assignment" class="btn">
        <i class="fas fa-plus"></i> Create Assignment
    </button>
</form>

<!-- Assignments Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>Current Assignments (<?= $totalCount ?>)</h3>
    </div>
    
    <!-- Search Section -->
    <div style="background: #0f1a35; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search Assignments
                </label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by teacher, course, or section..." 
                       value="<?= htmlspecialchars($searchTerm) ?>"
                       style="width: 100%;">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="assign_teachers.php" class="btn" style="background: #3a506b;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($assignments): ?>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                        <form method="POST">
                            <td>
                                <select name="teacher_id" required>
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?= $t['UserID'] ?>" <?= $a['TeacherID'] == $t['UserID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['FullName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="course_id" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= $c['CourseID'] ?>" <?= $a['CourseID'] == $c['CourseID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['CourseName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="section_id" required>
                                    <option value="">-- Select Section --</option>
                                    <?php foreach ($sections as $s): ?>
                                        <option value="<?= $s['SectionID'] ?>" <?= $a['SectionID'] == $s['SectionID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['SectionName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="assignment_id" value="<?= $a['ID'] ?>">
                                <button type="submit" name="edit_assignment" class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="submit" name="delete_assignment" class="btn btn-danger">
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
            <i class="fas fa-user-cog" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No assignments found<?= !empty($searchTerm) ? ' matching your search' : '' ?>.</p>
            <?php if (!empty($searchTerm)): ?>
                <a href="assign_teachers.php" class="btn btn-primary" style="margin-top: 15px;">
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
    
    document.querySelectorAll('table tbody select option:checked').forEach(option => {
        if (option.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
            option.closest('select').style.background = 'rgba(91, 192, 190, 0.15)';
            option.closest('select').style.borderColor = '#5bc0be';
        }
    });
});
<?php endif; ?>
</script>

<?php require '../includes/footer.php'; ?>