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

// Handle add section
if (isset($_POST['add_section'])) {
    $sectionName = trim($_POST['section_name']);

    if (!empty($sectionName)) {
        $check = $conn->prepare("SELECT * FROM section WHERE SectionName = :name");
        $check->execute([':name' => $sectionName]);
        if ($check->rowCount() > 0) {
            $message = "Section already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO section (SectionName) VALUES (:name)");
            $stmt->execute([':name' => $sectionName]);
            $message = "Section added successfully!";
        }
    } else {
        $message = "Please enter a section name.";
    }
}

// Handle edit section
if (isset($_POST['edit_section'])) {
    $id = intval($_POST['section_id']);
    $sectionName = trim($_POST['section_name']);

    if (!empty($sectionName)) {
        $stmt = $conn->prepare("UPDATE section SET SectionName = :name WHERE SectionID = :id");
        $stmt->execute([':name' => $sectionName, ':id' => $id]);
        $message = "Section updated successfully!";
    } else {
        $message = "Please enter a valid section name.";
    }
}

// Handle delete section
if (isset($_POST['delete_section'])) {
    $id = intval($_POST['section_id']);
    $stmt = $conn->prepare("DELETE FROM section WHERE SectionID = :id");
    $stmt->execute([':id' => $id]);
    $message = "Section deleted successfully!";
}

// Fetch all sections
$sections = $conn->query("SELECT * FROM section ORDER BY SectionName ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2><i class="fas fa-users-class"></i> Manage Class Sections</h2>

<?php if($message): ?>
    <p class="text-success"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- Add Section Form -->
<form method="POST" class="card">
    <h3>Add New Section</h3>
    <label>Section Name (e.g., BSCS-2A, ACT-APPDEV-2B):</label>
    <input type="text" name="section_name" required placeholder="Enter section name">
    <button type="submit" name="add_section" class="btn">Add Section</button>
</form>

<!-- Sections Table -->
<div class="card">
    <h3>All Sections</h3>
    <table class="styled-table">
        <tr>
            <th>Section Name</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($sections as $s): ?>
        <tr>
            <form method="POST">
                <td><input type="text" name="section_name" value="<?= htmlspecialchars($s['SectionName']) ?>" required></td>
                <td>
                    <input type="hidden" name="section_id" value="<?= $s['SectionID'] ?>">
                    <button type="submit" name="edit_section" class="btn">Edit</button>
                    <button type="submit" name="delete_section" class="btn btn-danger" onclick="return confirm('Delete this section?')">Delete</button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require '../includes/footer.php'; ?>