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

// Fetch all assignments
$assignments = $conn->query("
    SELECT tcs.ID, u.FullName as TeacherName, c.CourseName, s.SectionName
    FROM teacher_course_section tcs
    JOIN user u ON tcs.TeacherID = u.UserID
    JOIN course c ON tcs.CourseID = c.CourseID
    JOIN section s ON tcs.SectionID = s.SectionID
    ORDER BY u.FullName, c.CourseName, s.SectionName
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($message): ?>
    <p class="text-success"><?= htmlspecialchars($message) ?></p>
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

    <button type="submit" name="add_assignment" class="btn">Create Assignment</button>
</form>

<!-- Assignments Table -->
<div class="card">
    <h3>Current Assignments</h3>
    <?php if ($assignments): ?>
        <table class="styled-table">
            <tr>
                <th>Teacher</th>
                <th>Course</th>
                <th>Section</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['TeacherName']) ?></td>
                <td><?= htmlspecialchars($a['CourseName']) ?></td>
                <td><?= htmlspecialchars($a['SectionName']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="assignment_id" value="<?= $a['ID'] ?>">
                        <button type="submit" name="delete_assignment" class="btn btn-danger" onclick="return confirm('Delete this assignment?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No assignments yet.</p>
    <?php endif; ?>
</div>

<?php require '../includes/footer.php'; ?>