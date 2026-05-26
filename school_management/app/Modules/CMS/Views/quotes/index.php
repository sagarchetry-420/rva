<?php
/**
 * Quotes View (Admin)
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage the rotating quotes on the homepage</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('addQuoteModal')"><i class="fas fa-plus"></i> Add Quote</button>
    </div>
</div>

<?php if (empty($quotes)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-quote-left"></i></div>
        <p>No quotes found. Click "Add Quote" to create one.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Quote Text</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th class="actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td><strong>"<?php echo htmlspecialchars($q['quote_text']); ?>"</strong></td>
                        <td>- <?php echo htmlspecialchars($q['author']); ?></td>
                        <td>
                            <?php if ($q['is_active']): ?>
                                <span style="color:var(--success); font-weight:bold;"><i class="fas fa-eye"></i> Visible</span>
                            <?php else: ?>
                                <span style="color:var(--gray);"><i class="fas fa-eye-slash"></i> Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <div style="display:flex; gap:5px;">
                                <form method="POST" action="<?php echo moduleUrl('admin', 'quotes'); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-info" title="Toggle Visibility"><i class="fas fa-eye"></i></button>
                                </form>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'quotes'); ?>" style="display:inline;" onsubmit="return confirm('Delete this quote?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Add Quote Modal -->
<div id="addQuoteModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h2>Add Quote</h2>
            <span class="close" onclick="closeModal('addQuoteModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'quotes'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Quote Text *</label>
                    <textarea name="quote_text" rows="4" required placeholder="e.g. Education is not the learning of facts..."></textarea>
                </div>
                <div class="form-group">
                    <label>Author *</label>
                    <input type="text" name="author" required placeholder="e.g. Albert Einstein">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addQuoteModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Quote</button>
            </div>
        </form>
    </div>
</div>
