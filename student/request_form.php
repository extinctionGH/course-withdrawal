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

// Get student's section
$studentStmt = $conn->prepare("SELECT SectionID FROM user WHERE UserID = :id");
$studentStmt->execute([':id' => $_SESSION['userID']]);
$student = $studentStmt->fetch();
$studentSectionID = $student['SectionID'];

// Get all courses
$courses = $conn->query("SELECT * FROM course ORDER BY CourseName ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get teachers teaching in student's section with their courses
$teachersStmt = $conn->prepare("
    SELECT DISTINCT u.UserID, u.FullName, u.Email,
           GROUP_CONCAT(DISTINCT c.CourseName ORDER BY c.CourseName SEPARATOR ', ') as Courses
    FROM teacher_course_section tcs
    JOIN user u ON tcs.TeacherID = u.UserID
    JOIN course c ON tcs.CourseID = c.CourseID
    WHERE tcs.SectionID = :sectionID
    GROUP BY u.UserID, u.FullName, u.Email
    ORDER BY u.FullName ASC
");
$teachersStmt->execute([':sectionID' => $studentSectionID]);
$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseID = $_POST['course_id'];
    $teacherID = $_POST['teacher_id'];
    $reason = trim($_POST['reason']);

    if (!empty($courseID) && !empty($teacherID) && !empty($reason)) {
        // Verify teacher teaches this course in student's section
        $verifyStmt = $conn->prepare("
            SELECT * FROM teacher_course_section 
            WHERE TeacherID = :tid AND CourseID = :cid AND SectionID = :sid
        ");
        $verifyStmt->execute([
            ':tid' => $teacherID,
            ':cid' => $courseID,
            ':sid' => $studentSectionID
        ]);
        
        if ($verifyStmt->rowCount() > 0) {
            $stmt = $conn->prepare("
                INSERT INTO withdrawal_request (UserID, CourseID, TeacherID, SectionID, Reason, RequestDate, Status)
                VALUES (:userID, :courseID, :teacherID, :sectionID, :reason, NOW(), 'Pending')
            ");
            
            if ($stmt->execute([
                ':userID' => $_SESSION['userID'],
                ':courseID' => $courseID,
                ':teacherID' => $teacherID,
                ':sectionID' => $studentSectionID,
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
            $message = "Invalid selection. The selected teacher does not teach this course in your section.";
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
    
    <?php if (empty($studentSectionID)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            You have not been assigned to a section yet. Please contact your teacher/administrator.
        </div>
    <?php elseif (empty($teachers)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            No teachers are assigned to your section yet. Please contact your administrator.
        </div>
    <?php else: ?>
        <form method="POST" class="form-modern" id="withdrawalForm">
            <div class="form-group">
                <label><i class="fas fa-book"></i> Course</label>
                <select name="course_id" id="courseSelect" required>
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
                <select name="teacher_id" id="teacherSelect" required>
                    <option value="">-- Select a Teacher --</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= htmlspecialchars($teacher['UserID']) ?>" data-courses="<?= htmlspecialchars($teacher['Courses']) ?>">
                            <?= htmlspecialchars($teacher['FullName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #9ad1d4; display: block; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i> Only teachers assigned to your section are shown
                </small>
            </div>
            
            <div class="form-group" id="teacherCoursesInfo" style="display: none;">
                <div style="background: rgba(91, 192, 190, 0.1); padding: 12px; border-radius: 8px; border-left: 3px solid #5bc0be;">
                    <strong><i class="fas fa-book-open"></i> Courses taught by selected teacher:</strong>
                    <p id="coursesList" style="margin: 5px 0 0 0; color: #e0e6ed;"></p>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-comment-alt"></i> Reason for Withdrawal</label>
                <textarea name="reason" rows="5" required placeholder="Please explain your reason for withdrawing from this course..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const teacherSelect = document.getElementById('teacherSelect');
    const teacherCoursesInfo = document.getElementById('teacherCoursesInfo');
    const coursesList = document.getElementById('coursesList');
    
    if (teacherSelect) {
        teacherSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const courses = selectedOption.getAttribute('data-courses');
            
            if (courses && this.value) {
                coursesList.textContent = courses;
                teacherCoursesInfo.style.display = 'block';
            } else {
                teacherCoursesInfo.style.display = 'none';
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>