<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
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
        return true;
    }
    return false;
}

$projectId = $_GET['id'] ?? 0;

try {
    // Start transaction
    $conn->begin_transaction();

    // Get project info before deletion
    $stmt = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();

    if (!$project) {
        throw new Exception("Project not found.");
    }

    // 1. Delete project files and directories
    $projectPath = $project['file_path'];
    if ($projectPath && is_dir($projectPath)) {
        if (!recursiveDelete($projectPath)) {
            throw new Exception("Failed to delete project directory: $projectPath");
        }
    }

    // 2. Drop the database if it exists
    if (!empty($project['database_name'])) {
        $rootConn = new mysqli("localhost", "root", "");
        if ($rootConn->connect_error) {
            throw new Exception("Failed to connect to database server: " . $rootConn->connect_error);
        }

        $dbName = $rootConn->real_escape_string($project['database_name']);
        if (!$rootConn->query("DROP DATABASE IF EXISTS $dbName")) {
            throw new Exception("Failed to drop database: " . $rootConn->error);
        }
        $rootConn->close();
    }

    // 3. Delete project members
    $stmt = $conn->prepare("DELETE FROM project_members WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete project members: " . $stmt->error);
    }

    // 4. Delete project record
    $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete project record: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['delete_status'] = "Project deleted successfully.";
    header("Location: projects.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    $_SESSION['delete_error'] = "Error deleting project: " . $e->getMessage();
    header("Location: projects.php");
    exit;
}
?>
