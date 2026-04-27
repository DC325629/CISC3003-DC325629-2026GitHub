<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

render_header('Forgot Password', 'forgot-password', 0, $flashMessages ?? [], $dbError ?? null);
?>
<main>
    <div class="auth-container">
        <h1 class="auth-title">Forgot Password</h1>
        <div id="error-msg" class="error-msg" style="display: none;"></div>
        <div id="success-msg" class="success-msg" style="display: none;"></div>
        
        <div class="reset-info">
            <ion-icon name="mail-outline"></ion-icon>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
        </div>
        
        <form id="forgot-form">
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Your Email Address" required>
            </div>
            <button type="submit" class="btn-auth">Send Reset Link</button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</main>

<script>
    const forgotForm = document.getElementById('forgot-form');
    const errorDiv = document.getElementById('error-msg');
    const successDiv = document.getElementById('success-msg');

    forgotForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        const email = document.getElementById('email').value.trim();
        
        if (!email) {
            errorDiv.textContent = 'Please enter your email address';
            errorDiv.style.display = 'block';
            return;
        }
        
        if (!email.includes('@')) {
            errorDiv.textContent = 'Please enter a valid email address';
            errorDiv.style.display = 'block';
            return;
        }
        
        // Get users from localStorage
        const users = JSON.parse(localStorage.getItem('users') || '[]');
        const user = users.find(u => u.email === email);
        
        if (!user) {
            // For security, don't reveal if email exists or not
            successDiv.textContent = 'If your email exists in our system, you will receive a password reset link.';
            successDiv.style.display = 'block';
            document.getElementById('email').value = '';
            return;
        }
        
        // Generate reset token
        const resetToken = generateToken();
        const resetExpires = Date.now() + 3600000; // 1 hour
        
        // Save reset token to user
        user.resetToken = resetToken;
        user.resetExpires = resetExpires;
        
        // Update users in localStorage
        const updatedUsers = users.map(u => u.email === email ? user : u);
        localStorage.setItem('users', JSON.stringify(updatedUsers));
        
        // In a real app, you would send an email here
        // For demo, we'll simulate by showing the reset link
        const resetLink = window.location.origin + window.location.pathname.replace('forgot-password.php', 'reset-password.php') + '?token=' + resetToken;
        
        successDiv.innerHTML = `
            <strong>Demo Mode:</strong><br>
            A reset link has been simulated.<br>
            <a href="${resetLink}" style="color: #2e7d32; word-break: break-all;">Click here to reset your password</a>
            <br><br>
            <small>(In production, this link would be sent to your email.)</small>
        `;
        successDiv.style.display = 'block';
        document.getElementById('email').value = '';
    });
    
    function generateToken() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
</script>
<?php render_footer(); ?>