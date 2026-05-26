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

        <form method="POST" action="<?php echo moduleUrl('auth', 'reset-password'); ?>" class="login-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" required minlength="6" maxlength="255">
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
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
