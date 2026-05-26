<?php
/**
 * Public Admission Closed View
 */
?>
<div class="auth-container" style="max-width: 600px; padding: 40px; text-align: center;">
    <div class="auth-header">
        <div class="auth-logo">
            <i class="fa-solid fa-school fa-3x" style="color: var(--primary);"></i>
        </div>
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p style="margin-top: 15px; font-size: 16px; color: var(--text-muted);">
            We are sorry, but we are not currently accepting any new student applications at this time.
            <?php if (!empty($settings['deadline']) && strtotime($settings['deadline'] . ' 23:59:59') < time()): ?>
                <br><br>The admission deadline of <strong><?php echo date('d M Y', strtotime($settings['deadline'])); ?></strong> has passed.
            <?php endif; ?>
        </p>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="<?php echo moduleUrl('auth', 'login'); ?>" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
            <i class="fa-solid fa-arrow-left"></i> Return to Login
        </a>
    </div>
</div>

<style>
.auth-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    margin: 40px auto;
}
.btn-primary {
    background: var(--primary);
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
}
</style>
