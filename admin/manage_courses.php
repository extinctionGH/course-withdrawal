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

// Fetch all courses with search
$query = "SELECT * FROM course";
$params = [];

if (!empty($searchTerm)) {
    $query .= " WHERE CourseName LIKE :search";
    $params[':search'] = "%{$searchTerm}%";
}

$query .= " ORDER BY CourseName ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCount = count($courses);
?>

<?php if($message): ?>
    <div class="alert <?= strpos($message, 'success') !== false ? 'alert-success' : 'alert-error' ?>">
        <i class="fas fa-<?= strpos($message, 'success') !== false ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Add Course Form -->
<form method="POST" class="card">
    <h2><i class="fas fa-book"></i> Manage Courses</h2>
    <h3>Add New Course</h3>
    
    <label>Course Name:</label>
    <input type="text" name="course_name" required placeholder="Enter course name">
    
    <label>Units:</label>
    <input type="number" name="units" required min="1" placeholder="Enter number of units">
    
    <button type="submit" name="add_course" class="btn">
        <i class="fas fa-plus"></i> Add Course
    </button>
</form>

<!-- Courses Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>All Courses (<?= $totalCount ?>)</h3>
    </div>
    
    <!-- Search Section -->
    <div style="background: #0f1a35; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search Courses
                </label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by course name..." 
                       value="<?= htmlspecialchars($searchTerm) ?>"
                       style="width: 100%;">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="manage_courses.php" class="btn" style="background: #3a506b;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($courses): ?>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Units</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $c): ?>
                    <tr>
                        <form method="POST">
                            <td>
                                <input type="text" name="course_name" value="<?= htmlspecialchars($c['CourseName']) ?>" required>
                            </td>
                            <td>
                                <input type="number" name="units" value="<?= $c['Units'] ?>" min="1" required>
                            </td>
                            <td>
                                <input type="hidden" name="course_id" value="<?= $c['CourseID'] ?>">
                                <button type="submit" name="edit_course" class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="submit" name="delete_course" class="btn btn-danger">
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
            <i class="fas fa-book-open" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No courses found<?= !empty($searchTerm) ? ' matching your search' : '' ?>.</p>
            <?php if (!empty($searchTerm)): ?>
                <a href="manage_courses.php" class="btn btn-primary" style="margin-top: 15px;">
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
    
    document.querySelectorAll('table tbody input[type="text"]').forEach(input => {
        if (input.value.toLowerCase().includes(searchTerm.toLowerCase())) {
            input.style.background = 'rgba(91, 192, 190, 0.15)';
            input.style.borderColor = '#5bc0be';
        }
    });
});
<?php endif; ?>
</script>

<?php require '../includes/footer.php'; ?>