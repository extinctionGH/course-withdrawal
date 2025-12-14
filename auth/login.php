<?php
session_start();
require '../config/db_connect.php';

$db = new Database();
$conn = $db->connect();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {

        // Fetch user by email only (password will be verified separately)
        $stmt = $conn->prepare("SELECT * FROM user WHERE Email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch();

        // Verify password using password_verify for hashed passwords
        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['userID'] = $user['UserID'];
            $_SESSION['fullName'] = $user['FullName'];
            $_SESSION['role'] = $user['Role'];

            if (strtolower($user['Role']) === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../student/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Course Withdrawal Request System | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="login-page">
    <div class="table">
        <div class="center-table">
            <div class="common-box">
                <div class="box-image">
                    <img class="logo-image" src="../assets/images/logo.png" alt="Logo">
                    <div class="title">COURSE WITHDRAWAL REQUEST SYSTEM</div>
                </div>

                <?php if ($error): ?>
                    <div class="group group-error">
                        <div class="text-error"><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="textinput" required placeholder="Enter your email">
                    </div>
                    <div class="group">
                        <label>Password</label>
                        <input type="password" name="password" class="textinput" required
                            placeholder="Enter your password">
                    </div>
                    <div class="dash-footer">
                        <button type="submit" id="login-btn">Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>