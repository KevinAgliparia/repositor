<?php
session_start();
include '../config/db.php';

// Ensure the user is logged in and has the 'student' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = $_POST['delete_project_id'] ?? null;
    if ($projectId) {
        // Delete the project members first
        $sql = "DELETE FROM project_members WHERE project_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();

        // Now delete the project
        $sql = "DELETE FROM projects WHERE project_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $projectId, $userId);
        $stmt->execute();

        $_SESSION['delete_status'] = "Project deleted successfully.";
        header("Location: student.php");
        exit;
    }
}

header("Location: student.php");
exit;
