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

// Handle add new course
if (isset($_POST['add_course'])) {
    $name = trim($_POST['course_name']);
    $units = intval($_POST['units']);

    if (!empty($name) && $units > 0) {
        $stmt = $conn->prepare("INSERT INTO course (CourseName, Units) VALUES (:name, :units)");
        $stmt->execute([':name'=>$name, ':units'=>$units]);
        $message = "Course added successfully!";
    } else {
        $message = "Please enter a valid course name and units.";
    }
}

// Handle edit course
if (isset($_POST['edit_course'])) {
    $id = intval($_POST['course_id']);
    $name = trim($_POST['course_name']);
    $units = intval($_POST['units']);

    if (!empty($name) && $units > 0) {
        $stmt = $conn->prepare("UPDATE course SET CourseName=:name, Units=:units WHERE CourseID=:id");
        $stmt->execute([':name'=>$name, ':units'=>$units, ':id'=>$id]);
        $message = "Course updated successfully!";
    } else {
        $message = "Please enter a valid course name and units.";
    }
}

// Handle delete course
if (isset($_POST['delete_course'])) {
    $id = intval($_POST['course_id']);
    $stmt = $conn->prepare("DELETE FROM course WHERE CourseID=:id");
    $stmt->execute([':id'=>$id]);
    $message = "Course deleted successfully!";
}

// Fetch all courses
$courses = $conn->query("SELECT * FROM course ORDER BY CourseName ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if($message): ?>
    <p class="text-success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- Add Course Form -->
<form method="POST" class="card">
    <h2><i class="fas fa-book"></i> Manage Courses</h2>
    <h3>Add New Course</h3>
    
    <label>Course Name:</label>
    <input type="text" name="course_name" required>
    
    <label>Units:</label>
    <input type="number" name="units" required min="1">
    
    <button type="submit" name="add_course" class="btn">Add Course</button>
</form>

<!-- Courses Table -->
<div class="card">
    <h3>All Courses</h3>
    <table class="styled-table">
        <tr>
            <th>Course Name</th>
            <th>Units</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($courses as $c): ?>
        <tr>
            <form method="POST">
                <td><input type="text" name="course_name" value="<?= htmlspecialchars($c['CourseName']) ?>" required></td>
                <td><input type="number" name="units" value="<?= $c['Units'] ?>" min="1" required></td>
                <td>
                    <input type="hidden" name="course_id" value="<?= $c['CourseID'] ?>">
                    <button type="submit" name="edit_course" class="btn">Edit</button>
                    <button type="submit" name="delete_course" class="btn btn-danger" onclick="return confirm('Delete this course?')">Delete</button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require '../includes/footer.php'; ?>