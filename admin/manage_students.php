<?php
require '../includes/auth.php';
require '../config/db_connect.php';
require '../includes/header.php';

// Only teachers can access
if (strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$message = "";

// Handle add student - automatically assign to this teacher
if (isset($_POST['add_student'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($fullName) && !empty($email) && !empty($password)) {
        $check = $conn->prepare("SELECT * FROM user WHERE Email = :email");
        $check->execute([':email' => $email]);
        if ($check->rowCount() > 0) {
            $message = "Email already exists!";
        } else {
            // Add TeacherID when creating student
            $stmt = $conn->prepare("INSERT INTO user (FullName, Email, Password, Role, TeacherID) VALUES (:fullName, :email, :password, 'Student', :teacherID)");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':password' => $password,
                ':teacherID' => $_SESSION['userID']
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

    if (!empty($fullName) && !empty($email)) {
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE user SET FullName = :fullName, Email = :email, Password = :password WHERE UserID = :id AND Role = 'Student' AND TeacherID = :teacherID");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
                ':password' => $password,
                ':id' => $id,
                ':teacherID' => $_SESSION['userID']
            ]);
        } else {
            $stmt = $conn->prepare("UPDATE user SET FullName = :fullName, Email = :email WHERE UserID = :id AND Role = 'Student' AND TeacherID = :teacherID");
            $stmt->execute([
                ':fullName' => $fullName,
                ':email' => $email,
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

// Fetch only this teacher's students
$stmt = $conn->prepare("SELECT * FROM user WHERE Role = 'Student' AND TeacherID = :teacherID ORDER BY FullName ASC");
$stmt->execute([':teacherID' => $_SESSION['userID']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Manage My Students</h2>

<?php if($message): ?>
    <p class="text-success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- Add Student Form -->
<form method="POST" class="card">
    <h3>Add New Student</h3>
    <label>Full Name:</label>
    <input type="text" name="full_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Password:</label>
    <input type="password" name="password" required>

    <button type="submit" name="add_student" class="btn">Add Student</button>
</form>

<!-- Students Table -->
<table class="styled-table">
    <tr>
        <th>Full Name</th>
        <th>Email</th>
        <th>Password</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($students as $s): ?>
    <tr>
        <form method="POST">
            <td><input type="text" name="full_name" value="<?= htmlspecialchars($s['FullName']) ?>" required></td>
            <td><input type="email" name="email" value="<?= htmlspecialchars($s['Email']) ?>" required></td>
            <td><input type="text" name="password" value="<?= htmlspecialchars($s['Password']) ?>"></td>
            <td>
                <input type="hidden" name="user_id" value="<?= $s['UserID'] ?>">
                <button type="submit" name="edit_student" class="btn">Edit</button>
                <button type="submit" name="delete_student" class="btn btn-danger" onclick="return confirm('Delete this student?')">Delete</button>
            </td>
        </form>
    </tr>
    <?php endforeach; ?>
</table>

<?php require '../includes/footer.php'; ?>