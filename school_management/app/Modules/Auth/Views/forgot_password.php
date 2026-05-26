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
.form-text { display: block; font-size: 12px; color: #6c757d; margin-top: 4px; }
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

        <form method="POST" action="<?php echo moduleUrl('auth', 'forgot-password'); ?>" class="login-form">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required maxlength="255">
                </div>
                <small class="form-text">We'll send a password reset link to your email</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Send Reset Link <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>

        <div class="back-link">
            <a href="<?php echo moduleUrl('auth', 'login'); ?>"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</div>
