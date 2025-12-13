<?php
require '../includes/auth.php';
require '../config/db_connect.php';

if (strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$db = new Database();
$conn = $db->connect();
$teacherID = $_SESSION['userID'];

// Get filters from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query (same as reports.php)
$query = "
    SELECT wr.RequestID, u.FullName as StudentName, c.CourseName, wr.RequestDate, wr.Reason, wr.Status
    FROM withdrawal_request wr
    JOIN user u ON wr.UserID = u.UserID
    JOIN course c ON wr.CourseID = c.CourseID
    WHERE wr.TeacherID = :teacherID
";

$params = [':teacherID' => $teacherID];

if ($status_filter != 'all') {
    $query .= " AND wr.Status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND wr.RequestDate >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND wr.RequestDate <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY wr.RequestDate DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
$filename = "withdrawal_report_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Request ID', 'Student Name', 'Course', 'Request Date', 'Reason', 'Status']);

// Add data rows
foreach ($reports as $row) {
    fputcsv($output, [
        $row['RequestID'],
        $row['StudentName'],
        $row['CourseName'],
        $row['RequestDate'],
        $row['Reason'],
        $row['Status']
    ]);
}

fclose($output);
exit();
?>