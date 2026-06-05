<?php
/**
 * Forgot Password View
 */
?>
<style>
.login-box h1 { color: white; }
.input-with-icon input { padding-left: 45px !important; }
.input-with-icon { position: relative; }
.field-error { display: block; color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; }
.form-group input.error { border-color: #dc3545 !important; background-color: #fff5f5; }
.form-text { display: block; font-size: 12px; color: #6c757d; margin-top: 4px; }
.form-group input:valid:not(:placeholder-shown) { border-color: #28a745 !important; background-color: #f0fdf4; }
.form-group input:focus { outline: none; box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1); }
.back-link { text-align: center; margin-top: 20px; }
.back-link a { color: #1e3c72; font-size: 14px; text-decoration: none; }
.back-link a:hover { text-decoration: underline; }
</style>

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">
                <img src="<?php echo asset('img/logo.png'); ?>" alt="Logo" style="height:60px; width:auto; margin-bottom:10px;">
            </div>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Reset your password</p>
        </div>

        <form method="POST" action="<?php echo moduleUrl('auth', 'forgot-password'); ?>" class="login-form no-auto-validate">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 5px;">
                    <label for="email" style="margin-bottom: 0;">Email Address</label>
                    <span id="emailError" class="field-error" style="display: none; margin-top: 0;"></span>
                </div>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required maxlength="255">
                </div>
                <small class="form-text">We'll send a password reset link to your email</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Send Reset Link <i class="fa-solid fa-paper-plane"></i>
            </button>

            <div class="back-link">
                <a href="<?php echo moduleUrl('auth', 'login'); ?>"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
            </div>
        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');

    if (emailInput) {
        emailInput.addEventListener('blur', validateEmail);
        emailInput.addEventListener('input', validateEmail);
    }

    function validateEmail() {
        const value = emailInput.value.trim();
        const errorEl = document.getElementById('emailError');
        if (!value) {
            errorEl.innerText = 'Email is required.';
            errorEl.style.display = 'block';
            emailInput.classList.add('error');
            return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errorEl.innerText = 'Please enter a valid email address.';
            errorEl.style.display = 'block';
            emailInput.classList.add('error');
            return false;
        }
        errorEl.style.display = 'none';
        emailInput.classList.remove('error');
        return true;
    }

    document.querySelector('.login-form').addEventListener('submit', function(e) {
        if (!validateEmail()) {
            e.preventDefault();
            return false;
        }
        const btn = this.querySelector('.btn-primary');
        btn.innerHTML = 'Sending Link <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.classList.add('loading');
        setTimeout(() => { btn.disabled = true; }, 50);
    });
});
</script>
