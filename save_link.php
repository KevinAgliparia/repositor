<?php
include 'config/db.php';
$data = json_decode(file_get_contents("php://input"), true);
$project_id = $data['project_id'];
$external_link = $data['external_link'];

$sql = "UPDATE projects SET external_link = ? WHERE project_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $external_link, $project_id);
$stmt->execute();

echo $stmt->affected_rows ? 'Link updated successfully' : 'Error updating link';
?>
