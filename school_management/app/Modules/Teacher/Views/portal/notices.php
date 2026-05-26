<?php
/**
 * Teacher Notices View — Read-only list of notices for teachers
 * Variables: $notices
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Latest announcements and circulars</p>
    </div>
</div>

<?php if (empty($notices)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-bell-slash"></i></div>
        <p>No notices available at this time.</p>
    </div>
<?php else: ?>
    <?php foreach ($notices as $notice): ?>
        <div class="form-card" style="margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="margin: 0 0 5px 0;">
                        <i class="fas fa-thumbtack" style="color: #FF9800;"></i>
                        <?php echo htmlspecialchars($notice['title']); ?>
                    </h3>
                    <small style="color: #888;">
                        <i class="fas fa-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($notice['created_at'])); ?>
                        &nbsp;|&nbsp;
                        <i class="fas fa-users"></i> <?php echo htmlspecialchars($notice['target_audience']); ?>
                    </small>
                </div>
                <?php if (!empty($notice['attachment_path'])): ?>
                    <a href="/RVA/<?php echo htmlspecialchars($notice['attachment_path']); ?>" target="_blank" class="btn btn-sm btn-secondary">
                        <i class="fas fa-download"></i> Attachment
                    </a>
                <?php endif; ?>
            </div>
            <hr style="margin: 10px 0;">
            <div style="line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($notice['description'])); ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
