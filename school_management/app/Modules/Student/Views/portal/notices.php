<?php
/**
 * Student Notices View
 * Variables: $notices
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Latest announcements and circulars for students</p>
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

    <?php if (isset($totalPages) && $totalPages > 1): ?>
    <style>
        .pagination { display: flex; padding-left: 0; list-style: none; justify-content: center; gap: 5px; margin-top: 20px; }
        .page-item { margin: 0; }
        .page-link { position: relative; display: block; padding: 8px 16px; color: #0d6efd; text-decoration: none; background-color: #fff; border: 1px solid #dee2e6; border-radius: 4px; transition: all 0.2s; }
        .page-link:hover { z-index: 2; color: #0a58ca; background-color: #e9ecef; border-color: #dee2e6; }
        .page-item.active .page-link { z-index: 3; color: #fff; background-color: #0d6efd; border-color: #0d6efd; }
    </style>
    <nav aria-label="Notices pagination">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo isset($page) && $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

<?php endif; ?>
