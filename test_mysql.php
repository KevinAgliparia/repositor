<?php
// Test MySQL executable access
$mysqlPath = "C:/xampp/mysql/bin/mysql.exe";

if (!file_exists($mysqlPath)) {
    die("MySQL executable not found at: $mysqlPath");
}

// Test database creation
$testDb = "test_db_" . time();
$createCommand = sprintf(
    '"%s" -u root -e "CREATE DATABASE IF NOT EXISTS %s;"',
    $mysqlPath,
    escapeshellarg($testDb)
);

echo "Testing MySQL connection...<br>";
echo "Command: " . htmlspecialchars($createCommand) . "<br>";

exec($createCommand, $output, $returnVar);

if ($returnVar === 0) {
    echo "Success! Test database created.<br>";
    
    // Clean up - drop the test database
    $dropCommand = sprintf(
        '"%s" -u root -e "DROP DATABASE %s;"',
        $mysqlPath,
        escapeshellarg($testDb)
    );
    exec($dropCommand);
    echo "Test database cleaned up.<br>";
} else {
    echo "Error executing MySQL command.<br>";
    echo "Return code: $returnVar<br>";
    echo "Output: " . implode("<br>", $output);
}
?>
