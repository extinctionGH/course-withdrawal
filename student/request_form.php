<?php
require '../includes/auth.php';
require '../config/db_connect.php';
require '../includes/Mailer.php';
require '../includes/Notification.php';
include '../includes/header.php';

$db = new Database();
$conn = $db->connect();
$message = "";
$messageType = "";

// Get all courses
$courses = $conn->query("SELECT * FROM course ORDER BY CourseName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get all teachers
$teachers = $conn->query("SELECT UserID, FullName, Email FROM user WHERE Role = 'Admin' ORDER BY FullName ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseID = $_POST['course_id'];
    $teacherID = $_POST['teacher_id'];
    $reason = trim($_POST['reason']);

    if (!empty($courseID) && !empty($teacherID) && !empty($reason)) {
        $stmt = $conn->prepare("
            INSERT INTO withdrawal_request (UserID, CourseID, TeacherID, Reason, RequestDate, Status)
            VALUES (:userID, :courseID, :teacherID, :reason, NOW(), 'Pending')
        ");
        
        if ($stmt->execute([
            ':userID' => $_SESSION['userID'],
            ':courseID' => $courseID,
            ':teacherID' => $teacherID,
            ':reason' => $reason
        ])) {
            // Get details for email
            $courseStmt = $conn->prepare("SELECT CourseName FROM course WHERE CourseID = :id");
            $courseStmt->execute([':id' => $courseID]);
            $courseName = $courseStmt->fetch()['CourseName'];
            
            $teacherStmt = $conn->prepare("SELECT FullName, Email FROM user WHERE UserID = :id");
            $teacherStmt->execute([':id' => $teacherID]);
            $teacher = $teacherStmt->fetch();
            
            $studentStmt = $conn->prepare("SELECT FullName, Email FROM user WHERE UserID = :id");
            $studentStmt->execute([':id' => $_SESSION['userID']]);
            $student = $studentStmt->fetch();
            
            // Send emails
            $mailer = new Mailer();
            $mailer->sendRequestSubmittedEmail($student['Email'], $student['FullName'], $courseName, $teacher['FullName']);
            $mailer->sendNewRequestNotification($teacher['Email'], $teacher['FullName'], $student['FullName'], $courseName);
            
            // Create notifications
            $notif = new Notification($conn);
            
            // Notification for student
            $notif->create(
                $_SESSION['userID'],
                'Request Submitted',
                "Your withdrawal request for {$courseName} has been submitted to {$teacher['FullName']}.",
                'success'
            );
            
            // Notification for teacher
            $notif->create(
                $teacherID,
                'New Withdrawal Request',
                "{$student['FullName']} has submitted a withdrawal request for {$courseName}.",
                'info'
            );
            
            $message = "Withdrawal request submitted successfully! You will be notified via email once it's reviewed.";
            $messageType = "success";
        } else {
            $message = "Failed to submit request. Please try again.";
            $messageType = "error";
        }
    } else {
        $message = "Please fill out all fields.";
        $messageType = "error";
    }
}
?>

<div class="card">
    <h2><i class="fas fa-file-alt"></i> Submit Course Withdrawal Request</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="form-modern">
        <div class="form-group">
            <label><i class="fas fa-book"></i> Course</label>
            <select name="course_id" required>
                <option value="">-- Select a Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= htmlspecialchars($course['CourseID']) ?>">
                        <?= htmlspecialchars($course['CourseName']) ?> (<?= $course['Units'] ?> units)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-chalkboard-teacher"></i> Teacher/Professor</label>
            <select name="teacher_id" required>
                <option value="">-- Select a Teacher --</option>
                <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= htmlspecialchars($teacher['UserID']) ?>">
                        <?= htmlspecialchars($teacher['FullName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-comment-alt"></i> Reason for Withdrawal</label>
            <textarea name="reason" rows="5" required placeholder="Please explain your reason for withdrawing from this course..."></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Submit Request
        </button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>