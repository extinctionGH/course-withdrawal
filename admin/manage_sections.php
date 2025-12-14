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

// Fetch all sections with search
$query = "SELECT * FROM section";
$params = [];

if (!empty($searchTerm)) {
    $query .= " WHERE SectionName LIKE :search";
    $params[':search'] = "%{$searchTerm}%";
}

$query .= " ORDER BY SectionName ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalCount = count($sections);
?>

<?php if($message): ?>
    <div class="alert <?= strpos($message, 'success') !== false || strpos($message, 'added') !== false || strpos($message, 'updated') !== false || strpos($message, 'deleted') !== false ? 'alert-success' : 'alert-error' ?>">
        <i class="fas fa-<?= strpos($message, 'success') !== false || strpos($message, 'added') !== false || strpos($message, 'updated') !== false || strpos($message, 'deleted') !== false ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Add Section Form -->
<form method="POST" class="card">
    <h2><i class="fas fa-layer-group"></i> Manage Class Sections</h2>
    <h3>Add New Section</h3>
    
    <label>Section Name:</label>
    <input type="text" name="section_name" required placeholder="Enter section name">
    
    <button type="submit" name="add_section" class="btn">
        <i class="fas fa-plus"></i> Add Section
    </button>
</form>

<!-- Sections Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3>All Sections (<?= $totalCount ?>)</h3>
    </div>
    
    <!-- Search Section -->
    <div style="background: #0f1a35; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <form method="GET" style="display: grid; grid-template-columns: 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="margin-bottom: 8px; display: block; color: #9ad1d4; font-size: 14px;">
                    <i class="fas fa-search"></i> Search Sections
                </label>
                <input type="text" 
                       name="search" 
                       placeholder="Search by section name..." 
                       value="<?= htmlspecialchars($searchTerm) ?>"
                       style="width: 100%;">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="manage_sections.php" class="btn" style="background: #3a506b;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($sections): ?>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Section Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $s): ?>
                    <tr>
                        <form method="POST">
                            <td>
                                <input type="text" name="section_name" value="<?= htmlspecialchars($s['SectionName']) ?>" required>
                            </td>
                            <td>
                                <input type="hidden" name="section_id" value="<?= $s['SectionID'] ?>">
                                <button type="submit" name="edit_section" class="btn">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button type="submit" name="delete_section" class="btn btn-danger">
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
            <i class="fas fa-layer-group" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
            <p>No sections found<?= !empty($searchTerm) ? ' matching your search' : '' ?>.</p>
            <?php if (!empty($searchTerm)): ?>
                <a href="manage_sections.php" class="btn btn-primary" style="margin-top: 15px;">
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