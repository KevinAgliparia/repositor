<?php
session_start();
include '../config/db.php';

// Ensure the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$successMessage = "";

// Retrieve project ID from URL
$projectId = $_GET['id'] ?? null;
if (!$projectId) {
    header("Location: student.php");
    exit;
}

// Fetch the project data from the database
$sql = "SELECT p.*, GROUP_CONCAT(pm.member_name SEPARATOR ', ') AS members 
        FROM projects p
        LEFT JOIN project_members pm ON p.project_id = pm.project_id
        WHERE p.project_id = ? AND p.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $projectId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: student.php");
    exit;
}

$project = $result->fetch_assoc();

// Handle form submission to update the project
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'] ?? '';
    $projectMembers = $_POST['project_members'] ?? '';
    $abstract = $_POST['abstract'] ?? '';
    $externalLink = $_POST['external_link'] ?? '';

    // Handle photo upload (only if a new photo is provided)
    $photoPath = $project['photo_path'];
    if (isset($_FILES['project_photo']) && $_FILES['project_photo']['error'] === 0) {
        $photoDir = "../uploads/photos/$userId/";
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
        $photoPath = $photoDir . uniqid() . "_" . basename($_FILES['project_photo']['name']);
        if (!move_uploaded_file($_FILES['project_photo']['tmp_name'], $photoPath)) {
            die("Error uploading photo.");
        }
    }

    // Handle file upload (only if a new file is provided)
    $filePath = $project['file_path'];
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === 0) {
        $fileDir = "../uploads/files/$userId/";
        if (!is_dir($fileDir)) mkdir($fileDir, 0777, true);
        $filePath = $fileDir . uniqid() . "_" . basename($_FILES['project_file']['name']);
        if (!move_uploaded_file($_FILES['project_file']['tmp_name'], $filePath)) {
            die("Error uploading file.");
        }
    }

    // Update the project in the database
    $sql = "UPDATE projects SET project_name = ?, abstract = ?, external_link = ?, photo_path = ?, file_path = ? 
        WHERE project_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);

    // Corrected bind_param with the right number and types of parameters
    $stmt->bind_param("ssssssi", $projectName, $abstract, $externalLink, $photoPath, $filePath, $projectId, $userId);

    if (!$stmt->execute()) {
        die("Error updating project: " . $stmt->error);
    }


    // Update project members (delete existing and insert new ones)
    $conn->query("DELETE FROM project_members WHERE project_id = $projectId");

    $members = explode(',', $projectMembers);
    $memberStmt = $conn->prepare("INSERT INTO project_members (project_id, member_name) VALUES (?, ?)");
    foreach ($members as $member) {
        $member = trim($member);
        $memberStmt->bind_param("is", $projectId, $member);
        if (!$memberStmt->execute()) {
            die("Error inserting project member: " . $memberStmt->error);
        }
    }

    $_SESSION['upload_status'] = "Project updated successfully!";
    header("Location: student.php");
    exit;
}

// Display success message if any
if (isset($_SESSION['upload_status'])) {
    $successMessage = $_SESSION['upload_status'];
    unset($_SESSION['upload_status']);
}

?>

<?php include 'includes/head.php'; ?>

<?php include 'includes/navbar.php'; ?>

<div class="container main-content py-4">
        <h2>Edit Project</h2>

        <!-- Display success message if available -->
        <?php if ($successMessage): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
        <div class="card py-4 px-3 shadow-sm">
        <!-- Edit Project Form -->
        <form action="edit_project.php?id=<?php echo $project['project_id']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">

            <div class="mb-3">
                <label for="project_name" class="form-label">Project Name</label>
                <input type="text" class="form-control" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="project_members" class="form-label">Members</label>
                <input type="text" class="form-control" id="project_members" name="project_members" value="<?php echo htmlspecialchars($project['members']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="abstract" class="form-label">Abstract</label>
                <textarea class="form-control" id="abstract" name="abstract" rows="3"><?php echo htmlspecialchars($project['abstract']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="external_link" class="form-label">External Link</label>
                <input type="url" class="form-control" id="external_link" name="external_link" value="<?php echo htmlspecialchars($project['external_link']); ?>">
            </div>

            <div class="mb-3">
                <label for="project_photo" class="form-label">Project Photo</label>
                <input type="file" class="form-control" id="project_photo" name="project_photo">
                <small class="text-muted">Leave blank to keep the current photo.</small>
            </div>

            <div class="mb-3">
                <label for="project_file" class="form-label">Project File</label>
                <input type="file" class="form-control" id="project_file" name="project_file">
                <small class="text-muted">Leave blank to keep the current file.</small>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="student.php" class="btn btn-secondary">Cancel</a>
        </form>
        </div>
    </div>

 <?php include 'includes/footer.php'; ?>