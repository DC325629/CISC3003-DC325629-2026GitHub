<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && $_SESSION['logged_in'] === true) {
    echo json_encode([
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'full_name' => $_SESSION['user_name']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>