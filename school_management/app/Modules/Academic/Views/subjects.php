<?php
/**
 * Subjects Management View (Admin)
 * Variables: $subjects, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-book"></i> Subjects Management</h1>
        <p>Manage academic subjects catalog</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Subject</button>
    </div>
</div>

<!-- Search Bar -->
<div style="margin-bottom: 25px;">
    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="action" value="subjects">
        
        <div style="flex: 1; min-width: 250px; position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: var(--gray);"></i>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Search by Subject Name or Code..." style="width: 100%; border: 1px solid var(--border-color); background: #fff; padding: 10px 10px 10px 35px; border-radius: 4px;" maxlength="100">
        </div>
        
        <div>
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border-radius: 4px;"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?php echo moduleUrl('admin', 'subjects'); ?>" class="btn btn-secondary" style="padding: 10px 20px; border-radius: 4px; text-decoration: none;">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-container" style="padding-bottom: 100px;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Subject Name</th>
                <th>Subject Code</th>
                <th>Description</th>
                <th class="actions-cell">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($subjects)): ?>
            <?php foreach ($subjects as $s): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($s['subject_name']); ?></strong></td>
                <td><span style="background:var(--primary); color:#ffffff; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px;"><?php echo htmlspecialchars($s['subject_code']); ?></span></td>
                <td><?php echo htmlspecialchars($s['description'] ?? '—'); ?></td>
                <td class="actions-cell" style="display:flex; gap:5px; justify-content:flex-start;">
                    <button class="btn btn-sm btn-primary" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)'><i class="fas fa-edit"></i> Edit</button>
                    <details class="action-menu" style="position:relative;">
                        <summary style="list-style:none; cursor:pointer; padding:4px 10px; background:transparent; border:none; color:var(--text-color); font-size:16px; margin-top:2px;"><i class="fas fa-ellipsis-v"></i></summary>
                        <div style="position:absolute; right:0; top:100%; background:white; border:1px solid #ddd; box-shadow:0 2px 8px rgba(0,0,0,0.15); border-radius:4px; padding:5px; z-index:100; min-width:100px; margin-top:2px;">
                            <form method="POST" action="<?php echo moduleUrl('admin', 'subjects'); ?>" style="margin:0;" onsubmit="return confirmDelete('Delete this subject? This will also remove it from any assigned classes.')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="subject_id" value="<?php echo $s['subject_id']; ?>">
                                <button type="submit" style="border:none; background:none; color:#dc3545; width:100%; text-align:left; padding:8px; cursor:pointer; font-size:13px; font-weight:bold;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </details>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-book-open"></i></div><p>No subjects found.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if (isset($pagination)): ?>
        <div class="no-print" style="padding: 15px 20px; border-top: 1px solid var(--border-color);">
            <?php echo renderPagination($pagination); ?>
            <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> subjects)
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Add New Subject</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'subjects'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" id="add_subject_name" required maxlength="100" pattern="^[a-zA-Z0-9\s\-\&]+$" title="Only letters, numbers, spaces, hyphens, and ampersands are allowed." placeholder="e.g. Mathematics">
                </div>
                <div class="form-group">
                    <label>Subject Code (Auto-generated)</label>
                    <input type="text" name="subject_code" id="add_subject_code" required maxlength="20" pattern="^[a-zA-Z0-9\-]+$" title="Only letters, numbers, and hyphens are allowed." placeholder="Automatically generated" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" maxlength="255" placeholder="Optional description..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Edit Subject</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'subjects'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="subject_id" id="edit_subject_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Subject Name *</label>
                    <input type="text" name="subject_name" id="edit_subject_name" required maxlength="100" pattern="^[a-zA-Z0-9\s\-\&]+$" title="Only letters, numbers, spaces, hyphens, and ampersands are allowed.">
                </div>
                <div class="form-group">
                    <label>Subject Code (Auto-generated)</label>
                    <input type="text" name="subject_code" id="edit_subject_code" required maxlength="20" pattern="^[a-zA-Z0-9\-]+$" title="Only letters, numbers, and hyphens are allowed." readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3" maxlength="255"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(s) {
    document.getElementById('edit_subject_id').value = s.subject_id;
    document.getElementById('edit_subject_name').value = s.subject_name;
    document.getElementById('edit_subject_code').value = s.subject_code;
    document.getElementById('edit_description').value = s.description || '';
    openModal('editModal');
}

(function() {
    const nameInput = document.getElementById('add_subject_name');
    const codeInput = document.getElementById('add_subject_code');

    if (nameInput && codeInput) {
        nameInput.addEventListener('input', function() {
            if (!codeInput.dataset.manualEdit) {
                const name = this.value.trim();
                let code = '';
                if (name) {
                    const words = name.split(/\s+/);
                    if (words.length > 1) {
                        // Acronym for multi-word (e.g. "Computer Science" -> "CS")
                        code = words.map(w => w.charAt(0).toUpperCase()).join('').substring(0, 10);
                    } else {
                        // First 3-4 letters for single word (e.g. "Mathematics" -> "MATH")
                        code = name.substring(0, 4).toUpperCase();
                    }
                }
                codeInput.value = code;
            }
        });

        codeInput.addEventListener('input', function() {
            this.dataset.manualEdit = this.value.length > 0 ? 'true' : '';
        });
    }
})();
</script>
<style>
details.action-menu > summary::-webkit-details-marker {
    display: none;
}
</style>
<script>
// Close action menus when clicking outside
document.addEventListener('click', function(event) {
    const menus = document.querySelectorAll('details.action-menu');
    menus.forEach(menu => {
        if (menu.hasAttribute('open') && !menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});
</script>
