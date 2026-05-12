<?php
/**
 * Scenario C: User Dashboard (C.09)
 * Modified to match simple layout style, using provided styles.css
 */
session_start();
require 'connect.php';

// Protect the page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user data including registration date using prepared statement
$sql = "SELECT name, email, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Format registration date
$reg_date = date("F j, Y", strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <!-- Use the same styles.css as login/register page for consistency -->
    <link rel="stylesheet" href="styles.css">
    <!-- Additional simple overrides for dashboard layout -->
    <style>
        /* Keep container-like appearance but not overlapping with login form */
        body {
            height: auto;
            min-height: 100vh;
            margin: 0;
            padding: 40px 20px;
            justify-content: flex-start;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.1), 0 10px 10px rgba(0,0,0,0.08);
            width: 500px;
            max-width: 90%;
            padding: 30px 40px;
            text-align: center;
            margin: 20px auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .info p {
            margin: 12px 0;
            font-size: 16px;
        }
        .services {
            margin: 30px 0 20px;
            text-align: left;
            display: inline-block;
        }
        .services ul {
            list-style: none;
            padding: 0;
        }
        .services li {
            margin: 12px 0;
        }
        .services a {
            color: #3796FF;
            text-decoration: none;
            font-weight: bold;
        }
        .services a:hover {
            text-decoration: underline;
        }
        .logout-form {
            margin-top: 20px;
        }
        footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        button {
            background-color: #d93025;
            border-color: #d93025;
        }
        button:hover {
            background-color: #b5221a;
        }
    </style>
</head>
<body>
    <div class="dashboard-card">
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>

        <div class="info">
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Member Since:</strong> <?= $reg_date ?></p>
        </div>

        <div class="services">
            <h3>Services</h3>
            <ul>
                <li><a href="#">Update Profile (Placeholder)</a></li>
                <li><a href="#">Change Password (Placeholder)</a></li>
            </ul>
        </div>

        <form class="logout-form" action="logout.php" method="POST">
            <button type="submit">Log Out</button>
        </form>

        <footer>
            CISC3003 Web Programming: LAM KA WAI + DC325629 + 2026
        </footer>
    </div>
</body>
</html>