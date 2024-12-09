<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$searchTerm = $_GET['search'] ?? '';  // Get the search term from the query string

// Modify SQL query to filter projects based on the search term
$sql = "SELECT p.*, GROUP_CONCAT(pm.member_name SEPARATOR ', ') AS members 
        FROM projects p
        LEFT JOIN project_members pm ON p.project_id = pm.project_id
        WHERE p.user_id = ? 
        AND (p.project_name LIKE ? OR pm.member_name LIKE ?)
        GROUP BY p.project_id";

$searchTermWithWildcard = "%" . $searchTerm . "%";  // Add wildcards for SQL LIKE search
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $userId, $searchTermWithWildcard, $searchTermWithWildcard);
$stmt->execute();
$result = $stmt->get_result();

// Generate the HTML for the filtered projects
while ($row = $result->fetch_assoc()) {
?>
    <div class="col-md-6 col-lg-4 project-card">
        <div class="card shadow-sm h-100">
            <div class="image-container">
                <img src="<?php echo htmlspecialchars($row['photo_path']); ?>" class="card-img-top project-image" alt="Project Photo">
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($row['project_name']); ?></h5>
                <p class="text-muted">
                    <strong>Members:</strong>
                <ul class="list-unstyled mb-1">
                    <?php
                    $members = explode(',', $row['members']);
                    foreach ($members as $member) {
                        echo "<li><i class='bi bi-person-fill'></i> " . htmlspecialchars(trim($member)) . "</li>";
                    }
                    ?>
                </ul>
                </p>
                <p class="text-muted mb-3">
                    <strong>Uploaded:</strong>
                    <?php echo date("F j, Y, g:i A", strtotime($row['upload_date'])); ?>
                </p>
                <div class="d-flex justify-content-between">
                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" class="btn btn-sm btn-primary" download>
                        <i class="bi bi-download"></i> Download
                    </a>
                    <a href="<?php echo htmlspecialchars($row['external_link']); ?>" class="btn btn-sm btn-secondary" target="_blank">
                        <i class="bi bi-box-arrow-up-right"></i> View
                    </a>
                    <a href="edit_project.php?id=<?php echo $row['project_id']; ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delete-modal-<?php echo $row['project_id']; ?>">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>


    <?php include 'includes/delete_modal.php'; ?>
<?php
}
?>