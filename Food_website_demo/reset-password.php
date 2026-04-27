<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

render_header('Reset Password', 'reset-password', 0, $flashMessages ?? [], $dbError ?? null);
?>
<main>
    <div class="auth-container">
        <h1 class="auth-title">Reset Password</h1>
        <div id="error-msg" class="error-msg" style="display: none;"></div>
        <div id="success-msg" class="success-msg" style="display: none;"></div>
        
        <form id="reset-form">
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="New Password (min 6 chars)" required>
                <div class="password-strength">
                    <div id="strength-bar" class="strength-bar"></div>
                    <span id="strength-text"></span>
                </div>
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
            </div>
            <button type="submit" class="btn-auth-register-page">Reset Password</button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    
    const resetForm = document.getElementById('reset-form');
    const errorDiv = document.getElementById('error-msg');
    const successDiv = document.getElementById('success-msg');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    
    if (!token) {
        errorDiv.textContent = 'Invalid or missing reset token. Please request a new password reset.';
        errorDiv.style.display = 'block';
        resetForm.style.display = 'none';
    }
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        
        strengthBar.className = 'strength-bar';
        if (strength <= 1) {
            strengthBar.classList.add('weak');
            strengthText.innerHTML = 'Weak - at least 6 characters';
        } else if (strength <= 3) {
            strengthBar.classList.add('medium');
            strengthText.innerHTML = 'Medium - add uppercase letters or numbers';
        } else {
            strengthBar.classList.add('strong');
            strengthText.innerHTML = 'Strong - good password';
        }
    });
    
    resetForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        errorDiv.style.display = 'none';
        successDiv.style.display = 'none';
        
        const password = passwordInput.value;
        const confirmPassword = confirmInput.value;
        
        if (!password) {
            errorDiv.textContent = 'Please enter a new password';
            errorDiv.style.display = 'block';
            return;
        }
        
        if (password.length < 6) {
            errorDiv.textContent = 'Password must be at least 6 characters';
            errorDiv.style.display = 'block';
            return;
        }
        
        if (password !== confirmPassword) {
            errorDiv.textContent = 'Passwords do not match';
            errorDiv.style.display = 'block';
            return;
        }
        
        const users = JSON.parse(localStorage.getItem('users') || '[]');
        
        const userIndex = users.findIndex(u => u.resetToken === token && u.resetExpires > Date.now());
        
        if (userIndex === -1) {
            errorDiv.textContent = 'Invalid or expired reset token. Please request a new password reset.';
            errorDiv.style.display = 'block';
            return;
        }
        
        users[userIndex].password = password;

        delete users[userIndex].resetToken;
        delete users[userIndex].resetExpires;
        
        localStorage.setItem('users', JSON.stringify(users));
        
        successDiv.innerHTML = 'Password reset successful! Redirecting to login page...';
        successDiv.style.display = 'block';
        
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
    });
</script>
<?php render_footer(); ?>