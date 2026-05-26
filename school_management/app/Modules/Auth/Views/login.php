<?php
/**
 * Login View — Content portion (wrapped by auth layout)
 * Variables: $pageTitle
 */
$oldEmail = old('email', '');
?>
<style>
.login-box h1 { color: white; }
.input-with-icon input { padding-left: 45px !important; }
.input-with-icon { position: relative; }
.toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666; z-index: 10; }
.forgot-password-link { text-align: right; margin-top: 8px; }
.forgot-password-link a { color: #1e3c72; font-size: 14px; text-decoration: none; }
.forgot-password-link a:hover { text-decoration: underline; }
.role-selector { display: flex; gap: 10px; margin-bottom: 20px; }
.role-option { flex: 1; text-align: center; }
.role-option input { display: none; }
.role-label { display: block; padding: 12px; background: #f4f7f6; border: 2px solid #ddd; border-radius: 10px; cursor: pointer; transition: all 0.3s; font-weight: 600; color: #666; }
.role-option input:checked + .role-label { background: #1e3c72; border-color: #1e3c72; color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.field-error { display: block; color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; }
.form-group input.error { border-color: #dc3545 !important; background-color: #fff5f5; }
.form-text { display: block; font-size: 12px; color: #6c757d; margin-top: 4px; }
.form-group input:valid:not(:placeholder-shown) { border-color: #28a745 !important; background-color: #f0fdf4; }
.form-group input:focus { outline: none; box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1); }
</style>

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <div class="login-logo">
                <img src="<?php echo asset('img/logo.png'); ?>" alt="Logo" style="height:60px; width:auto; margin-bottom:10px;">
            </div>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Sign in to your account</p>
        </div>

        <form method="POST" action="<?php echo moduleUrl('auth', 'login'); ?>" class="login-form no-auto-validate" id="loginForm">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required
                           value="<?php echo htmlspecialchars($oldEmail); ?>" maxlength="255">
                    <span id="emailError" class="field-error" style="display: none;"></span>
                </div>
                <small class="form-text">Enter your registered email address</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required maxlength="255">
                    <span class="toggle-password" onclick="togglePasswordVisibility()">
                        <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                    </span>
                    <span id="passwordError" class="field-error" style="display: none;"></span>
                </div>
                <small class="form-text">Enter your password</small>
                <div class="forgot-password-link">
                    <a href="<?php echo moduleUrl('auth', 'forgot-password'); ?>">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-login">
                Sign In <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    emailInput.addEventListener('blur', validateEmail);
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);
    passwordInput.addEventListener('input', validatePassword);

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

    function validatePassword() {
        const value = passwordInput.value;
        const errorEl = document.getElementById('passwordError');
        if (!value) {
            errorEl.innerText = 'Password is required.';
            errorEl.style.display = 'block';
            passwordInput.classList.add('error');
            return false;
        }
        errorEl.style.display = 'none';
        passwordInput.classList.remove('error');
        return true;
    }

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        if (!validateEmail() || !validatePassword()) {
            e.preventDefault();
            return false;
        }
        const btn = this.querySelector('.btn-login');
        btn.innerHTML = 'Signing In <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.classList.add('loading');
        setTimeout(() => { btn.disabled = true; }, 50);
    });

    window.togglePasswordVisibility = function() {
        const pi = document.getElementById('password');
        const ti = document.getElementById('togglePasswordIcon');
        if (pi.type === 'password') { pi.type = 'text'; ti.classList.replace('fa-eye', 'fa-eye-slash'); }
        else { pi.type = 'password'; ti.classList.replace('fa-eye-slash', 'fa-eye'); }
    };
});
</script>
