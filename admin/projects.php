<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get projects with member names
$sql = "SELECT p.*, GROUP_CONCAT(pm.member_name) as members 
        FROM projects p 
        LEFT JOIN project_members pm ON p.project_id = pm.project_id 
        GROUP BY p.project_id 
        ORDER BY p.project_id DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Database error: " . $conn->error);
}
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container main-content py-4">
    <!-- Display success message if available -->
    <?php if (isset($_SESSION['upload_status'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['upload_status'];
            unset($_SESSION['upload_status']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>System Projects</h2>
        <a href="add_project.php" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Add New Project
        </a>
    </div>

    <!-- Projects grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <!-- Project Image -->
                        <img src="<?php echo $row['photo_path'] ? $row['photo_path'] : '../assets/img/default-project.jpg'; ?>" 
                             class="card-img-top" alt="Project Thumbnail"
                             style="height: 200px; object-fit: cover;">
                        
                        <div class="card-body">
                            <!-- Project Title -->
                            <h5 class="card-title text-truncate">
                                <?php echo htmlspecialchars($row['project_name']); ?>
                            </h5>
                            
                            <!-- Project Info -->
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event"></i> <?php echo $row['project_year']; ?>
                                </small>
                                <small class="text-muted ms-2">
                                    <i class="bi bi-code-square"></i> <?php echo $row['project_abbreviation']; ?>
                                </small>
                                <?php if ($row['database_name']): ?>
                                    <small class="text-muted ms-2">
                                        <i class="bi bi-database"></i> <?php echo $row['database_name']; ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Project Abstract -->
                            <p class="card-text small text-muted mb-2" style="height: 3em; overflow: hidden;">
                                <?php echo htmlspecialchars($row['abstract']); ?>
                            </p>

                            <!-- Project Members -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-people"></i> 
                                    <?php echo htmlspecialchars($row['members']); ?>
                                </small>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <?php if ($row['external_link']): ?>
                                    <a href="<?php echo htmlspecialchars($row['external_link']); ?>" 
                                       class="btn btn-sm btn-primary flex-grow-1" target="_blank">
                                        <i class="bi bi-box-arrow-up-right"></i> View Project
                                    </a>
                                <?php endif; ?>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="edit_project.php?id=<?php echo $row['project_id']; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="download_project.php?id=<?php echo $row['project_id']; ?>">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" 
                                               onclick="confirmDelete(<?php echo $row['project_id']; ?>)">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    No projects found. Click the "Add New Project" button to create one.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this project? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Project</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(projectId) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('confirmDeleteBtn').href = `delete_project.php?id=${projectId}`;
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>
