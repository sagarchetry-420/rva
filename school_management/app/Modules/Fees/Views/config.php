<?php
/**
 * Fee Configuration View (Admin)
 * Variables: $categories, $services, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-cogs"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Define fee structures and optional services</p>
    </div>
</div>

<div class="row" style="display:flex; gap:20px; flex-wrap:wrap;">
    <!-- Fee Categories -->
    <div class="col-md-6" style="flex:1; min-width:300px;">
        <div class="form-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0;">Fee Categories</h3>
                <button class="btn btn-sm btn-primary" onclick="openModal('catModal')"><i class="fas fa-plus"></i> Add</button>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr><th>Category</th><th>Description</th><th style="width: 80px; text-align: center;">Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['category_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($c['description'] ?? '—'); ?></td>
                        <td style="text-align: center;">
                            <form method="POST" action="<?php echo moduleUrl('admin', 'fee_config'); ?>" onsubmit="return confirm('Are you sure you want to delete this category?');" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_id" value="<?php echo $c['category_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modals -->
<div id="catModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h2>Add Fee Category</h2>
            <span class="close" onclick="closeModal('catModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'fee_config'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add_category">
            <div class="modal-body">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="category_name" required placeholder="e.g. Tuition Fee" maxlength="50" pattern="^[a-zA-Z0-9\s\-_]+$" title="Only letters, numbers, spaces, hyphens, and underscores are allowed.">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" maxlength="255" placeholder="Optional description..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

