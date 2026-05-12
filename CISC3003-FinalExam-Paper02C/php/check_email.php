<?php
/**
 * Scenario C: Backend script for Ajax email validation
 */
require 'connect.php';

// Return JSON format
header('Content-Type: application/json');

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    
    // Check if email exists in database
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If a row is returned, the email is NOT available
    if ($result->num_rows > 0) {
        echo json_encode(["available" => false]);
    } else {
        echo json_encode(["available" => true]);
    }
    
    $stmt->close();
}
$conn->close();
?>