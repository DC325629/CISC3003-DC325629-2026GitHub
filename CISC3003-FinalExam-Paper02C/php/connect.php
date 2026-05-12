<?php
/**
 * Database connection for Scenario C
 */
$host = "localhost";
$dbname = "paper02_c";
$username = "root";
$password = "";
$port = 3306; // Configured for your XAMPP setup

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>