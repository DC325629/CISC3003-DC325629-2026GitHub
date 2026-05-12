<?php
/**
 * Scenario C: Server-side validation, database insertion, and account activation email
 */
require 'connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer (ensure vendor folder is in the project root)
require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // C.02: Validate signup data on the server in PHP
    if (empty($_POST["name"]) || empty($_POST["email"]) || empty($_POST["password"])) {
        die("All fields are required. <a href='register.php'>Go back</a>");
    }

    if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        die("Valid email is required. <a href='register.php'>Go back</a>");
    }

    if (strlen($_POST["password"]) < 6) {
        die("Password must be at least 6 characters. <a href='register.php'>Go back</a>");
    }

    if ($_POST["password"] !== $_POST["password_confirmation"]) {
        die("Passwords must match. <a href='register.php'>Go back</a>");
    }

    // Secure the password using PHP's password_hash function
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
    
    // C.08: Generate a random activation hash for email confirmation
    $activation_token = bin2hex(random_bytes(16));
    $activation_hash = hash("sha256", $activation_token);

    // C.03: Save the signup data to MySQL database using PHP
    $sql = "INSERT INTO users (name, email, password_hash, account_activation_hash, is_active) 
            VALUES (?, ?, ?, ?, 0)";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssss", $_POST["name"], $_POST["email"], $password_hash, $activation_hash);
        
        try {
            if ($stmt->execute()) {
                
                // Send activation email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'CrispyTeam05@gmail.com'; // Your Gmail
                    $mail->Password = 'vrxixiokfkwqdmyn';       // Your App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 465;
                    $mail->SMTPSecure = 'ssl'; 

                    $mail->setFrom('CrispyTeam05@gmail.com', 'CISC3003 System');
                    $mail->addAddress($_POST["email"], $_POST["name"]);

                    $mail->isHTML(true);
                    $mail->Subject = 'Account Activation Required';
                    
                    // Create the activation link (adjust the path to match your local setup)
                    $activation_link = "http://localhost/CISC3003-FinalExam-Paper02C/php/activate.php?token=$activation_token";
                    
                    $mail->Body = "
                        <h2>Hello, {$_POST['name']}!</h2>
                        <p>Thank you for signing up. To complete your registration and log in, please click the link below to activate your account:</p>
                        <p><a href='$activation_link'>Activate My Account</a></p>
                    ";

                    $mail->send();
                    echo "<h1>Signup Successful!</h1>";
                    echo "<p>Please check your email to activate your account before logging in.</p>";
                    echo "<p><a href='login.php'>Go to Login Page</a></p>";
                    
                } catch (Exception $e) {
                    echo "Signup successful, but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            }
        } catch (mysqli_sql_exception $e) {
            // Handle duplicate email error
            if ($e->getCode() == 1062) {
                die("Email already taken. <a href='register.php'>Try again</a>");
            } else {
                die("Database error: " . $e->getMessage());
            }
        }
    }
}
?>