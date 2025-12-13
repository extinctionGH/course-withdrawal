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

// Fetch all teachers/admins
$teachers = $conn->query("SELECT * FROM user WHERE Role = 'Admin' ORDER BY FullName ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2><i class="fas fa-chalkboard-teacher"></i> Manage Teachers/Admin</h2>

<?php if($message): ?>
    <p class="text-success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- Add Teacher Form -->
<form method="POST" class="card">
    <h3>Add New Teacher/Admin</h3>
    <label>Full Name:</label>
    <input type="text" name="full_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Password:</label>
    <input type="password" name="password" required>

    <button type="submit" name="add_teacher" class="btn">Add Teacher/Admin</button>
</form>

<!-- Teachers Table -->
<div class="card">
    <h3>All Teachers/Admin</h3>
    <table class="styled-table">
        <tr>
            <th>Full Name</th>
            <th>Email</th>
            <th>Password</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($teachers as $t): ?>
        <tr>
            <form method="POST">
                <td><input type="text" name="full_name" value="<?= htmlspecialchars($t['FullName']) ?>" required></td>
                <td><input type="email" name="email" value="<?= htmlspecialchars($t['Email']) ?>" required></td>
                <td><input type="text" name="password" value="<?= htmlspecialchars($t['Password']) ?>"></td>
                <td>
                    <input type="hidden" name="user_id" value="<?= $t['UserID'] ?>">
                    <button type="submit" name="edit_teacher" class="btn">Edit</button>
                    <?php if ($t['UserID'] != $_SESSION['userID']): ?>
                        <button type="submit" name="delete_teacher" class="btn btn-danger" onclick="return confirm('Delete this teacher/admin?')">Delete</button>
                    <?php endif; ?>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require '../includes/footer.php'; ?>