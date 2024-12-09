<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Helper function to recursively copy files
function recursiveCopy($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0777, true);
    }
    while (($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recursiveCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Helper function to recursively delete directory
function recursiveDelete($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    recursiveDelete($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

$projectId = $_GET['id'] ?? 0;

// Get project data
$stmt = $conn->prepare("
    SELECT p.*, GROUP_CONCAT(pm.member_name) as members 
    FROM projects p 
    LEFT JOIN project_members pm ON p.project_id = pm.project_id 
    WHERE p.project_id = ?
    GROUP BY p.project_id
");
$stmt->bind_param("i", $projectId);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'] ?? '';
    $projectAbbreviation = strtoupper($_POST['project_abbreviation'] ?? '');
    $projectYear = $_POST['project_year'] ?? date('Y');
    $projectMembers = $_POST['project_members'] ?? '';
    $abstract = $_POST['abstract'] ?? '';
    $databaseName = $_POST['database_name'] ?? '';
    $oldProjectPath = $project['file_path'];

    // Check if we need to move the project to a new directory
    $newProjectBaseDir = "../Project/$projectYear/$projectAbbreviation/";
    if ($oldProjectPath && $oldProjectPath !== $newProjectBaseDir) {
        // Create new directory if it doesn't exist
        if (!is_dir($newProjectBaseDir)) {
            mkdir($newProjectBaseDir, 0777, true);
        }
        
        // Move project files to new location
        if (is_dir($oldProjectPath)) {
            recursiveCopy($oldProjectPath, $newProjectBaseDir);
            recursiveDelete($oldProjectPath);
        }
    }

    // Handle photo upload
    $photoPath = $project['photo_path'];
    if (isset($_FILES['project_photo']) && $_FILES['project_photo']['error'] === 0) {
        $photoDir = $newProjectBaseDir . "images/";
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
        
        // Delete old photo if it exists
        if ($photoPath && file_exists($photoPath)) {
            unlink($photoPath);
        }
        
        $photoPath = $photoDir . "thumbnail." . pathinfo($_FILES['project_photo']['name'], PATHINFO_EXTENSION);
        if (!move_uploaded_file($_FILES['project_photo']['tmp_name'], $photoPath)) {
            die("Error uploading photo.");
        }
    }

    // Handle ZIP file upload and extraction
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === 0) {
        $tempZipPath = $newProjectBaseDir . basename($_FILES['project_file']['name']);
        if (!move_uploaded_file($_FILES['project_file']['tmp_name'], $tempZipPath)) {
            die("Error uploading ZIP file.");
        }

        try {
            // Extract ZIP file
            $zip = new ZipArchive;
            if ($zip->open($tempZipPath) === TRUE) {
                // Search for index file in the ZIP
                $indexFile = null;
                $indexPath = '';
                
                // Common index file names
                $indexFiles = ['index.php', 'index.html', 'home.php', 'main.php'];
                
                // Find the first matching index file
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $basename = basename($filename);
                    if (in_array(strtolower($basename), $indexFiles)) {
                        $indexFile = $basename;
                        $indexPath = dirname($filename);
                        break;
                    }
                }
                
                // If found an index file, extract to appropriate directory
                if ($indexFile) {
                    // If index file is not in root, need to handle extraction differently
                    if (!empty($indexPath) && $indexPath !== '.') {
                        // Create a temporary extraction directory
                        $tempExtractPath = $newProjectBaseDir . 'temp_extract/';
                        if (!is_dir($tempExtractPath)) {
                            mkdir($tempExtractPath, 0777, true);
                        }
                        
                        // Extract everything first
                        $zip->extractTo($tempExtractPath);
                        
                        // Move files from the index file's directory to project directory
                        $sourcePath = $tempExtractPath . $indexPath;
                        recursiveCopy($sourcePath, $newProjectBaseDir);
                        
                        // Clean up temporary directory
                        recursiveDelete($tempExtractPath);
                    } else {
                        // Index file is in root, extract directly
                        $zip->extractTo($newProjectBaseDir);
                    }
                } else {
                    // No index file found, extract everything to root
                    $zip->extractTo($newProjectBaseDir);
                }
                
                $zip->close();
                unlink($tempZipPath); // Delete the ZIP file after extraction
            } else {
                throw new Exception("Error extracting ZIP file.");
            }
        } catch (Exception $e) {
            die("ZIP Error: " . $e->getMessage());
        }
    }

    // Handle database file
    if (isset($_FILES['database_file']) && $_FILES['database_file']['error'] === 0) {
        // Save the SQL file to the database directory with original filename
        $dbFileDir = $newProjectBaseDir . "database/";
        if (!is_dir($dbFileDir)) mkdir($dbFileDir, 0777, true);
        
        $originalFileName = basename($_FILES['database_file']['name']);
        $dbFilePath = $dbFileDir . $originalFileName;
        
        if (!move_uploaded_file($_FILES['database_file']['tmp_name'], $dbFilePath)) {
            die("Error saving database file.");
        }

        try {
            // Create a new database connection without selecting a database
            $rootConn = new mysqli("localhost", "root", "");
            
            if ($rootConn->connect_error) {
                throw new Exception("Connection failed: " . $rootConn->connect_error);
            }

            // Drop the database if it exists and create it fresh
            $dropDb = "DROP DATABASE IF EXISTS " . $rootConn->real_escape_string($databaseName);
            if (!$rootConn->query($dropDb)) {
                throw new Exception("Error dropping existing database: " . $rootConn->error);
            }

            // Create the database
            $createDb = "CREATE DATABASE " . $rootConn->real_escape_string($databaseName);
            if (!$rootConn->query($createDb)) {
                throw new Exception("Error creating database: " . $rootConn->error);
            }

            // Select the database
            if (!$rootConn->select_db($databaseName)) {
                throw new Exception("Error selecting database: " . $rootConn->error);
            }

            // Read and execute the SQL file
            $sql = file_get_contents($dbFilePath);
            if ($sql === false) {
                throw new Exception("Error reading SQL file");
            }

            // Split the SQL file into individual queries
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if (!$rootConn->query($query)) {
                        throw new Exception("Error executing query: " . $rootConn->error . "\nQuery: " . $query);
                    }
                }
            }

            // Save a backup with original filename + .bak extension
            copy($dbFilePath, $dbFileDir . $originalFileName . ".bak");
            
            // Close the root connection
            $rootConn->close();

            $_SESSION['db_status'] = "Database '$databaseName' updated successfully!";

        } catch (Exception $e) {
            // If there's an error, attempt to drop the partially created database
            if (isset($rootConn)) {
                $rootConn->query("DROP DATABASE IF EXISTS " . $rootConn->real_escape_string($databaseName));
                $rootConn->close();
            }
            die("Database Error: " . $e->getMessage());
        }
    }

    // Generate external link for the project
    $externalLink = "http://localhost/repository/Project/{$projectYear}/{$projectAbbreviation}/index.php";

    // Update project in database
    $stmt = $conn->prepare("UPDATE projects SET 
        project_name = ?, 
        project_abbreviation = ?, 
        project_year = ?, 
        abstract = ?, 
        database_name = ?, 
        photo_path = ?, 
        file_path = ?, 
        external_link = ?
        WHERE project_id = ?"
    );
    
    $stmt->bind_param(
        "ssssssssi", 
        $projectName, 
        $projectAbbreviation, 
        $projectYear, 
        $abstract, 
        $databaseName, 
        $photoPath, 
        $newProjectBaseDir, 
        $externalLink, 
        $projectId
    );

    if (!$stmt->execute()) {
        // Handle potential database update error
        $_SESSION['edit_error'] = "Error updating project: " . $stmt->error;
        header("Location: edit_project.php?id={$projectId}");
        exit;
    }

    // Update project members
    // First, delete existing members
    $conn->query("DELETE FROM project_members WHERE project_id = " . $projectId);
    
    // Then insert new members
    $members = explode(',', $projectMembers);
    $memberStmt = $conn->prepare("INSERT INTO project_members (project_id, member_name) VALUES (?, ?)");
    foreach ($members as $member) {
        $member = trim($member);
        if (!empty($member)) {
            $memberStmt->bind_param("is", $projectId, $member);
            if (!$memberStmt->execute()) {
                die("Error updating project member: " . $memberStmt->error);
            }
        }
    }

    $_SESSION['upload_status'] = "Project updated successfully! Access it at: $externalLink";
    header("Location: projects.php");
    exit;
}
?>

<?php include 'includes/head.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container main-content py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit System Project</h5>
                </div>
                <div class="card-body">
                    <form action="edit_project.php?id=<?php echo $projectId; ?>" method="post" enctype="multipart/form-data">
                        <!-- Project Title -->
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Title</label>
                            <input type="text" name="project_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                        </div>

                        <!-- Project Abbreviation -->
                        <div class="mb-3">
                            <label for="project_abbreviation" class="form-label">Project Abbreviation</label>
                            <input type="text" name="project_abbreviation" class="form-control" 
                                   pattern="[A-Za-z]+" title="Only letters allowed"
                                   value="<?php echo htmlspecialchars($project['project_abbreviation']); ?>" required>
                            <div class="form-text">Example: ABC (only letters allowed)</div>
                        </div>

                        <!-- Project Year -->
                        <div class="mb-3">
                            <label for="project_year" class="form-label">Year</label>
                            <select name="project_year" class="form-control" required>
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= 2020; $year--) {
                                    $selected = ($year == $project['project_year']) ? 'selected' : '';
                                    echo "<option value=\"$year\" $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Project Members -->
                        <div class="mb-3">
                            <label for="project_members" class="form-label">Project Members</label>
                            <textarea name="project_members" class="form-control" rows="2" 
                                    placeholder="Enter member names, separated by commas" required><?php 
                                    echo htmlspecialchars($project['members']); 
                            ?></textarea>
                            <div class="form-text">Separate multiple members with commas</div>
                        </div>

                        <!-- Abstract -->
                        <div class="mb-3">
                            <label for="abstract" class="form-label">Abstract</label>
                            <textarea name="abstract" class="form-control" rows="4" required><?php 
                                echo htmlspecialchars($project['abstract']); 
                            ?></textarea>
                        </div>

                        <!-- Database Name -->
                        <div class="mb-3">
                            <label for="database_name" class="form-label">Database Name</label>
                            <input type="text" name="database_name" class="form-control" 
                                   pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed"
                                   value="<?php echo htmlspecialchars($project['database_name']); ?>" required>
                            <div class="form-text">Example: project_db (only letters, numbers, and underscores)</div>
                        </div>

                        <!-- MySQL Database File -->
                        <div class="mb-3">
                            <label for="database_file" class="form-label">MySQL Database File</label>
                            <input type="file" name="database_file" class="form-control" accept=".sql">
                            <div class="form-text">Upload new .sql file only if you want to update the database</div>
                        </div>

                        <!-- Project Photo -->
                        <div class="mb-3">
                            <label for="project_photo" class="form-label">System Project Photo</label>
                            <?php if ($project['photo_path']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $project['photo_path']; ?>" 
                                         alt="Current Project Photo" 
                                         style="max-width: 200px; height: auto;"
                                         class="img-thumbnail">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="project_photo" class="form-control" accept="image/*">
                            <div class="form-text">Upload new image only if you want to change it. Recommended: Square image (800x800 pixels)</div>
                        </div>

                        <!-- Project ZIP File -->
                        <div class="mb-3">
                            <label for="project_file" class="form-label">System Project ZIP File</label>
                            <input type="file" name="project_file" class="form-control" accept=".zip">
                            <div class="form-text">Upload new ZIP file only if you want to update the project files</div>
                        </div>

                        <div class="text-end">
                            <a href="projects.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>