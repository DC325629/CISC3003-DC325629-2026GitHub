<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scenario C - User Signup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <script src="../js/script.js" defer></script>
</head>
<body>
    <h1>Sign Up</h1>
    
    <form action="process_signup.php" method="POST" id="signup-form" novalidate>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email:</label>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1rem;">
            <input type="email" id="email" name="email" required style="margin-bottom: 0;">
            <span id="email-availability-status" style="font-size: 0.9em; white-space: nowrap;"></span>
        </div>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="password_confirmation">Confirm Password:</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>

        <button type="submit">Sign Up</button>
    </form>

    <p>Already have an account? <a href="login.php">Log in here</a></p>

    <hr>
    <footer>
        <p>CISC3003 Web Programming: LAM KA WAI + DC325629 + 2026</p>
    </footer>
</body>
</html>