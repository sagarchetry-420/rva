<?php
/**
 * Reset Password View
 * Variables: $token
 */
?>
<style>
.login-box h1 { color: white; }
.input-with-icon input { padding-left: 45px !important; }
.input-with-icon { position: relative; }
.field-error { display: block; color: #dc3545; font-size: 12px; margin-top: 5px; font-weight: 500; }
.form-group input.error { border-color: #dc3545 !important; background-color: #fff5f5; }
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
            <p>Set a new password</p>
        </div>

        <form method="POST" action="<?php echo moduleUrl('auth', 'reset-password'); ?>" class="login-form no-auto-validate">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 5px;">
                    <label for="password" style="margin-bottom: 0;">New Password</label>
                    <span id="passwordError" class="field-error" style="display: none; margin-top: 0;"></span>
                </div>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" required minlength="6" maxlength="255">
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 5px;">
                    <label for="password_confirmation" style="margin-bottom: 0;">Confirm Password</label>
                    <span id="passwordConfirmError" class="field-error" style="display: none; margin-top: 0;"></span>
                </div>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat password" required minlength="6" maxlength="255">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Reset Password <i class="fa-solid fa-key"></i>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwdInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');

    if (pwdInput) {
        pwdInput.addEventListener('blur', validatePassword);
        pwdInput.addEventListener('input', validatePassword);
    }
    if (confirmInput) {
        confirmInput.addEventListener('blur', validateConfirm);
        confirmInput.addEventListener('input', validateConfirm);
    }

    function validatePassword() {
        const value = pwdInput.value;
        const errorEl = document.getElementById('passwordError');
        if (!value || value.length < 6) {
            errorEl.innerText = 'Password must be at least 6 characters.';
            errorEl.style.display = 'block';
            pwdInput.classList.add('error');
            return false;
        }
        errorEl.style.display = 'none';
        pwdInput.classList.remove('error');
        return true;
    }

    function validateConfirm() {
        const pwd = pwdInput.value;
        const confirm = confirmInput.value;
        const errorEl = document.getElementById('passwordConfirmError');
        if (confirm !== pwd) {
            errorEl.innerText = 'Passwords do not match.';
            errorEl.style.display = 'block';
            confirmInput.classList.add('error');
            return false;
        }
        errorEl.style.display = 'none';
        confirmInput.classList.remove('error');
        return true;
    }

    document.querySelector('.login-form').addEventListener('submit', function(e) {
        if (!validatePassword() || !validateConfirm()) {
            e.preventDefault();
            return false;
        }
        const btn = this.querySelector('.btn-primary');
        btn.innerHTML = 'Resetting <i class="fa-solid fa-spinner fa-spin"></i>';
        btn.classList.add('loading');
        setTimeout(() => { btn.disabled = true; }, 50);
    });
});
</script>
