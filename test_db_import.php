<?php
// Test database creation and import
try {
    // Create a test database name
    $testDb = "test_db_" . time();
    
    // Create connection without database
    $conn = new mysqli("localhost", "root", "");
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected successfully<br>";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS " . $conn->real_escape_string($testDb);
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully<br>";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    // Select the database
    if ($conn->select_db($testDb)) {
        echo "Database selected successfully<br>";
    } else {
        throw new Exception("Error selecting database: " . $conn->error);
    }
    
    // Create a test table
    $sql = "CREATE TABLE test_table (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(30) NOT NULL
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully<br>";
    } else {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // Clean up - drop the test database
    $sql = "DROP DATABASE " . $conn->real_escape_string($testDb);
    if ($conn->query($sql) === TRUE) {
        echo "Test database cleaned up successfully<br>";
    } else {
        throw new Exception("Error dropping database: " . $conn->error);
    }
    
    $conn->close();
    echo "Test completed successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
