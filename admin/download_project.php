<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$projectId = $_GET['id'] ?? 0;

try {
    // Get project info
    $stmt = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        throw new Exception("Project not found.");
    }

    // Construct the project directory path
    $baseProjectPath = "../Project/{$project['project_year']}/{$project['project_abbreviation']}";
    $projectPath = realpath($baseProjectPath);
    
    if (!$projectPath || !is_dir($projectPath)) {
        throw new Exception("Project directory not found: $baseProjectPath");
    }

    // Create ZIP file
    $zipFileName = preg_replace('/[^\w\-\.]/', '_', $project['project_name']) . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipFileName;

    // Create safe folder name from project title
    $projectFolderName = preg_replace('/[^\w\-\.]/', '_', $project['project_name']);

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception("Failed to create ZIP archive.");
    }

    // Define image file extensions to exclude
    $imageExtensions = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico');

    // Add project files to ZIP
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $baseLen = strlen($projectPath);
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            // Skip image files
            if (!in_array($extension, $imageExtensions)) {
                $relativePath = $projectFolderName . '/' . substr($filePath, $baseLen + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    // Export and add database if exists
    if (!empty($project['database_name'])) {
        $dbName = $project['database_name'];
        $sqlFile = sys_get_temp_dir() . '/' . $dbName . '.sql';
        
        // Export database
        $command = sprintf(
            'mysqldump --no-tablespaces -u root %s > %s',
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );
        
        exec($command);
        
        if (file_exists($sqlFile)) {
            $zip->addFile($sqlFile, 'database/' . $dbName . '.sql');
        }
    }

    // Add project info file
    $infoContent = "Project Information\n" .
                   "===================\n\n" .
                   "Project Name: " . $project['project_name'] . "\n" .
                   "Year: " . $project['project_year'] . "\n" .
                   "Abbreviation: " . $project['project_abbreviation'] . "\n" .
                   "Database Name: " . $project['database_name'] . "\n" .
                   "Project URL: " . $project['external_link'] . "\n\n" .
                   "Abstract\n" .
                   "========\n" .
                   $project['abstract'] . "\n\n" .
                   "Project Structure\n" .
                   "================\n" .
                   "- /" . $projectFolderName . "/  : Contains all project source files (excluding images)\n" .
                   "- /database/      : Contains database backup (if applicable)\n" .
                   "- project_info.txt: This file\n\n" .
                   "Note: Image files have been excluded from this package to reduce size.";

    $zip->addFromString('project_info.txt', $infoContent);
    $zip->close();

    // Clean up database dump if it exists
    if (isset($sqlFile) && file_exists($sqlFile)) {
        unlink($sqlFile);
    }

    // Send ZIP file
    if (file_exists($zipPath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-cache');
        
        ob_clean();
        flush();
        readfile($zipPath);
        unlink($zipPath);
        exit;
    } else {
        throw new Exception("Failed to create download file.");
    }

} catch (Exception $e) {
    $_SESSION['download_error'] = "Error downloading project: " . $e->getMessage();
    header("Location: projects.php");
    exit;
}
?>
