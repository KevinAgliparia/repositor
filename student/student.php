<?php
session_start();
include '../config/db.php';

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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$successMessage = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'] ?? '';
    $projectAbbreviation = strtoupper($_POST['project_abbreviation'] ?? '');
    $projectYear = $_POST['project_year'] ?? date('Y');
    $projectMembers = $_POST['project_members'] ?? '';
    $abstract = $_POST['abstract'] ?? '';
    $databaseName = $_POST['database_name'] ?? '';

    // Create project directory structure
    $projectBaseDir = "../Project/$projectYear/$projectAbbreviation/";
    if (!is_dir($projectBaseDir)) {
        mkdir($projectBaseDir, 0777, true);
    }

    // Handle photo upload
    $photoPath = null;
    if (isset($_FILES['project_photo']) && $_FILES['project_photo']['error'] === 0) {
        $photoDir = $projectBaseDir . "images/";
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
        $photoPath = $photoDir . "thumbnail." . pathinfo($_FILES['project_photo']['name'], PATHINFO_EXTENSION);
        if (!move_uploaded_file($_FILES['project_photo']['tmp_name'], $photoPath)) {
            die("Error uploading photo.");
        }
    }

    // Handle ZIP file upload and extraction
    $zipPath = null;
    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === 0) {
        $tempZipPath = $projectBaseDir . basename($_FILES['project_file']['name']);
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
                        $tempExtractPath = $projectBaseDir . 'temp_extract/';
                        if (!is_dir($tempExtractPath)) {
                            mkdir($tempExtractPath, 0777, true);
                        }
                        
                        // Extract everything first
                        $zip->extractTo($tempExtractPath);
                        
                        // Move files from the index file's directory to project directory
                        $sourcePath = $tempExtractPath . $indexPath;
                        recursiveCopy($sourcePath, $projectBaseDir);
                        
                        // Clean up temporary directory
                        recursiveDelete($tempExtractPath);
                    } else {
                        // Index file is in root, extract directly
                        $zip->extractTo($projectBaseDir);
                    }
                } else {
                    // No index file found, extract everything to root
                    $zip->extractTo($projectBaseDir);
                }
                
                $zip->close();
                unlink($tempZipPath); // Delete the ZIP file after extraction
            } else {
                throw new Exception("Error extracting ZIP file.");
            }
        } catch (Exception $e) {
            die("ZIP Error: " . $e->getMessage());
        }
        $zipPath = $projectBaseDir;
    }

    // Handle database file
    if (isset($_FILES['database_file']) && $_FILES['database_file']['error'] === 0) {
        // Save the SQL file to the database directory with original filename
        $dbFileDir = $projectBaseDir . "database/";
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

            $_SESSION['db_status'] = "Database '$databaseName' created and imported successfully!";

        } catch (Exception $e) {
            // If there's an error, attempt to drop the partially created database
            if (isset($rootConn)) {
                $rootConn->query("DROP DATABASE IF EXISTS " . $rootConn->real_escape_string($databaseName));
                $rootConn->close();
            }
            die("Database Error: " . $e->getMessage());
        }
    }

    // Generate project URL
    $projectUrl = "http://localhost/repository/Project/$projectYear/$projectAbbreviation/";

    // Insert project into database
    $sql = "INSERT INTO projects (user_id, project_name, project_abbreviation, project_year, abstract, database_name, photo_path, file_path, external_link) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssss", $userId, $projectName, $projectAbbreviation, $projectYear, $abstract, $databaseName, $photoPath, $zipPath, $projectUrl);

    if (!$stmt->execute()) {
        die("Error inserting project: " . $stmt->error);
    }

    // Insert project members
    $projectId = $conn->insert_id;
    $members = explode(',', $projectMembers);
    $memberStmt = $conn->prepare("INSERT INTO project_members (project_id, member_name) VALUES (?, ?)");
    foreach ($members as $member) {
        $member = trim($member);
        if (!empty($member)) {
            $memberStmt->bind_param("is", $projectId, $member);
            if (!$memberStmt->execute()) {
                die("Error inserting project member: " . $memberStmt->error);
            }
        }
    }

    $_SESSION['upload_status'] = "Project uploaded successfully! Access it at: $projectUrl";
    header("Location: student.php");
    exit;
}


// Retrieve and clear status messages
if (isset($_SESSION['upload_status'])) {
    $successMessage = $_SESSION['upload_status'];
    unset($_SESSION['upload_status']);
}
if (isset($_SESSION['delete_status'])) {
    $deleteMessage = $_SESSION['delete_status'];
    unset($_SESSION['delete_status']);
}

// Fetch uploaded projects
$sql = "SELECT p.*, GROUP_CONCAT(pm.member_name SEPARATOR ', ') AS members 
        FROM projects p
        LEFT JOIN project_members pm ON p.project_id = pm.project_id
        WHERE p.user_id = ?
        GROUP BY p.project_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>


<?php include 'includes/head.php'; ?>

<?php include 'includes/navbar.php'; ?>

<div class="container main-content py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4">Your Projects</h1>
        <div>
            <input type="text" id="search-bar" class="form-control d-inline-block me-2" style="width: 250px;" placeholder="Search projects..." oninput="searchProjects()">
            <script>
                function searchProjects() {
                    var searchTerm = document.getElementById('search-bar').value;

                    // Create an AJAX request
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "search_projects.php?search=" + encodeURIComponent(searchTerm), true);

                    // Handle the response from the server
                    xhr.onload = function() {
                        if (xhr.status == 200) {
                            // Update the projects container with the filtered projects
                            document.getElementById('projects-container').innerHTML = xhr.responseText;
                        }
                    };

                    // Send the request
                    xhr.send();
                }
            </script>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#upload-modal">
                <i class="bi bi-upload"></i> Upload New Project
            </button>
        </div>
    </div>

    <!-- Projects Section (this will be populated with initial data on page load) -->
    <div class="row g-4" id="projects-container">
        <?php while ($row = $result->fetch_assoc()) { ?>
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
        <?php } ?>


    </div>
</div>

<?php include 'includes/upload_modal.php'; ?>

<?php include 'includes/footer.php'; ?>