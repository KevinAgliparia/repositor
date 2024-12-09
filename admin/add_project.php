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

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectName = $_POST['project_name'] ?? '';
    $projectAbbreviation = strtoupper($_POST['project_abbreviation'] ?? '');
    $projectYear = $_POST['project_year'] ?? date('Y');
    $projectMembers = $_POST['project_members'] ?? '';
    $abstract = $_POST['abstract'] ?? '';
    $databaseName = $_POST['database_name'] ?? '';

    // Check for existing project with same name and year
    $checkStmt = $conn->prepare("SELECT project_id FROM projects WHERE project_name = ? AND project_year = ?");
    $checkStmt->bind_param("ss", $projectName, $projectYear);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $_SESSION['upload_error'] = "A project with the same name and year already exists.";
        header("Location: add_project.php");
        exit;
    }

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
        // Increase script execution time
        ini_set('max_execution_time', 600); // 10 minutes
        ini_set('max_input_time', 600);
        ini_set('memory_limit', '512M');

        $tempZipPath = $projectBaseDir . basename($_FILES['project_file']['name']);
        if (!move_uploaded_file($_FILES['project_file']['tmp_name'], $tempZipPath)) {
            die("Error uploading ZIP file.");
        }

        try {
            // Extract ZIP file
            $zip = new ZipArchive;
            if ($zip->open($tempZipPath) === TRUE) {
                // Create a temporary extraction directory
                $tempExtractPath = $projectBaseDir . 'temp_extract/';
                if (!is_dir($tempExtractPath)) {
                    mkdir($tempExtractPath, 0777, true);
                }

                // Optimized extraction with progress tracking
                $totalFiles = $zip->numFiles;
                $extractedFiles = 0;

                // Extract files in batches to prevent timeout
                for ($i = 0; $i < $totalFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    
                    // Skip directories
                    if (substr($filename, -1) === '/') continue;

                    // Construct full destination path
                    $destinationPath = $tempExtractPath . $filename;
                    
                    // Ensure destination directory exists
                    $destDir = dirname($destinationPath);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0777, true);
                    }

                    // Extract individual file
                    if ($zip->extractTo($destDir, $filename)) {
                        $extractedFiles++;
                    }

                    // Optional: Add a small pause to prevent server overload
                    if ($extractedFiles % 50 === 0) {
                        usleep(10000); // 10 milliseconds
                    }
                }

                $zip->close();

                // Find potential index files and project root
                $indexFiles = ['index.php', 'index.html', 'home.php', 'main.php', 'app.php', 'default.php'];

                // Recursive function to find index files with depth preference
                function findIndexFiles($directory, $indexFiles) {
                    $indexLocations = [];
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    // Collect all index file locations
                    foreach ($iterator as $file) {
                        if ($file->isFile() && in_array(strtolower($file->getFilename()), $indexFiles)) {
                            $indexLocations[] = [
                                'path' => $file->getPathname(),
                                'depth' => $iterator->getDepth(),
                                'directory' => $file->getPath()
                            ];
                        }
                    }

                    // Sort index locations
                    usort($indexLocations, function($a, $b) {
                        $aIsRoot = ($a['depth'] === 0);
                        $bIsRoot = ($b['depth'] === 0);
                        
                        if ($aIsRoot && !$bIsRoot) return -1;
                        if (!$aIsRoot && $bIsRoot) return 1;
                        
                        return $a['depth'] - $b['depth'];
                    });

                    return !empty($indexLocations) ? $indexLocations[0] : null;
                }

                // Find the index file with depth preference
                $indexFileInfo = findIndexFiles($tempExtractPath, $indexFiles);

                if ($indexFileInfo) {
                    // Determine the project root (directory containing the index file)
                    $projectRoot = $indexFileInfo['directory'];

                    // Optimized file copying with progress tracking
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    $copiedFiles = 0;
                    foreach ($iterator as $item) {
                        $relativePath = substr($item->getPathname(), strlen($projectRoot) + 1);
                        $destinationPath = $projectBaseDir . $relativePath;

                        if ($item->isDir()) {
                            if (!is_dir($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                        } else {
                            // Ensure destination directory exists
                            $destDir = dirname($destinationPath);
                            if (!is_dir($destDir)) {
                                mkdir($destDir, 0777, true);
                            }
                            
                            // Copy file with optional progress tracking
                            copy($item->getPathname(), $destinationPath);
                            $copiedFiles++;

                            // Optional: Add a small pause to prevent server overload
                            if ($copiedFiles % 50 === 0) {
                                usleep(10000); // 10 milliseconds
                            }
                        }
                    }
                } else {
                    // If no index file found, copy entire extracted contents
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($tempExtractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    $copiedFiles = 0;
                    foreach ($iterator as $item) {
                        $relativePath = substr($item->getPathname(), strlen($tempExtractPath) + 1);
                        $destinationPath = $projectBaseDir . $relativePath;

                        if ($item->isDir()) {
                            if (!is_dir($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                        } else {
                            // Ensure destination directory exists
                            $destDir = dirname($destinationPath);
                            if (!is_dir($destDir)) {
                                mkdir($destDir, 0777, true);
                            }
                            
                            // Copy file with optional progress tracking
                            copy($item->getPathname(), $destinationPath);
                            $copiedFiles++;

                            // Optional: Add a small pause to prevent server overload
                            if ($copiedFiles % 50 === 0) {
                                usleep(10000); // 10 milliseconds
                            }
                        }
                    }
                }

                // Clean up temporary extraction directory
                recursiveDelete($tempExtractPath);

                // Remove the original ZIP file
                unlink($tempZipPath);
            } else {
                throw new Exception("Error opening ZIP file.");
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

    // Generate external link for the project
    $externalLink = "http://localhost/repository/Project/{$projectYear}/{$projectAbbreviation}/index.php";

    // Prepare SQL to insert project with correct URL
    $stmt = $conn->prepare("INSERT INTO projects (
        project_name, 
        project_abbreviation, 
        project_year, 
        abstract, 
        file_path, 
        photo_path, 
        database_name, 
        external_link, 
        user_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $stmt->bind_param(
        "ssssssssi", 
        $projectName, 
        $projectAbbreviation, 
        $projectYear, 
        $abstract, 
        $projectBaseDir, 
        $photoPath, 
        $databaseName, 
        $externalLink, 
        $userId
    );

    if (!$stmt->execute()) {
        // Handle potential database insertion error
        $_SESSION['upload_error'] = "Error saving project: " . $stmt->error;
        header("Location: add_project.php");
        exit;
    }

    $projectId = $conn->insert_id;

    // Insert project members
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

    $_SESSION['upload_status'] = "Project uploaded successfully! Access it at: $externalLink";
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
                    <h5 class="card-title mb-0">Upload New System Project</h5>
                </div>
                <div class="card-body">
                    <form id="projectUploadForm" action="add_project.php" method="post" enctype="multipart/form-data" onsubmit="return validateAndPreventDoubleSubmit()">
                        <!-- Project Title -->
                        <div class="mb-3">
                            <label for="project_name" class="form-label">Project Title</label>
                            <input type="text" name="project_name" class="form-control" required>
                        </div>

                        <!-- Project Abbreviation -->
                        <div class="mb-3">
                            <label for="project_abbreviation" class="form-label">Project Abbreviation</label>
                            <input type="text" name="project_abbreviation" class="form-control" 
                                   pattern="[A-Za-z]+" title="Only letters allowed" required>
                            <div class="form-text">Example: ABC (only letters allowed)</div>
                        </div>

                        <!-- Project Year -->
                        <div class="mb-3">
                            <label for="project_year" class="form-label">Year</label>
                            <select name="project_year" class="form-control" required>
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= 2020; $year--) {
                                    echo "<option value=\"$year\">$year</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Project Members -->
                        <div class="mb-3">
                            <label for="project_members" class="form-label">Project Members</label>
                            <textarea name="project_members" class="form-control" rows="2" 
                                    placeholder="Enter member names, separated by commas" required></textarea>
                            <div class="form-text">Separate multiple members with commas</div>
                        </div>

                        <!-- Abstract -->
                        <div class="mb-3">
                            <label for="abstract" class="form-label">Abstract</label>
                            <textarea name="abstract" class="form-control" rows="4" required></textarea>
                        </div>

                        <!-- Database Name -->
                        <div class="mb-3">
                            <label for="database_name" class="form-label">Database Name</label>
                            <input type="text" name="database_name" class="form-control" 
                                   pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed" required>
                            <div class="form-text">Example: project_db (only letters, numbers, and underscores)</div>
                        </div>

                        <!-- MySQL Database File -->
                        <div class="mb-3">
                            <label for="database_file" class="form-label">MySQL Database File</label>
                            <input type="file" name="database_file" class="form-control" accept=".sql" required>
                            <div class="form-text">Upload .sql file exported from phpMyAdmin</div>
                        </div>

                        <!-- Project Photo -->
                        <div class="mb-3">
                            <label for="project_photo" class="form-label">System Project Photo</label>
                            <input type="file" name="project_photo" class="form-control" accept="image/*" required>
                            <div class="form-text">Recommended: Square image (800x800 pixels)</div>
                        </div>

                        <!-- Project ZIP File -->
                        <div class="mb-3">
                            <label for="project_file" class="form-label">System Project ZIP File</label>
                            <input type="file" name="project_file" class="form-control" accept=".zip" required>
                            <div class="form-text">Upload your project files as a ZIP archive</div>
                        </div>

                        <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 bg-light bg-opacity-75 d-none" style="z-index: 9999;">
                            <div class="d-flex justify-content-center align-items-center h-100">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Uploading...</span>
                                </div>
                                <span class="ms-2">Uploading project... Please do not close or refresh the page.</span>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="projects.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" id="submitBtn" class="btn btn-success">
                                <i class="bi bi-cloud-upload"></i> Upload Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let isSubmitting = false;

function validateAndPreventDoubleSubmit() {
    // Check if already submitting
    if (isSubmitting) {
        alert('Project upload is in progress. Please wait.');
        return false;
    }

    // Validate form
    const projectFile = document.getElementById('project_file');
    if (!projectFile.files.length) {
        alert('Please select a project ZIP file.');
        return false;
    }

    // Disable submit button and show loading overlay
    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    submitBtn.disabled = true;
    loadingOverlay.classList.remove('d-none');

    // Set a timeout to re-enable submission if something goes wrong
    setTimeout(() => {
        if (isSubmitting) {
            isSubmitting = false;
            submitBtn.disabled = false;
            loadingOverlay.classList.add('d-none');
        }
    }, 120000); // 2 minutes timeout

    return true;
}

// Prevent multiple form submissions via browser back button
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        isSubmitting = false;
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('loadingOverlay').classList.add('d-none');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
