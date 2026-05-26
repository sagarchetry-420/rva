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

<div class="table-container">
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
                <td><span style="background:var(--primary-light); color:var(--primary); padding:2px 6px; border-radius:4px; font-weight:bold; font-size:12px;"><?php echo htmlspecialchars($s['subject_code']); ?></span></td>
                <td><?php echo htmlspecialchars($s['description'] ?? '—'); ?></td>
                <td class="actions-cell">
                    <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)'><i class="fas fa-edit"></i> Edit</button>
                    <form method="POST" action="<?php echo moduleUrl('admin', 'subjects'); ?>" style="display:inline" onsubmit="return confirmDelete('Delete this subject? This will also remove it from any assigned classes.')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="subject_id" value="<?php echo $s['subject_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-book-open"></i></div><p>No subjects found.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
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
                    <input type="text" name="subject_name" id="add_subject_name" required maxlength="100" placeholder="e.g. Mathematics">
                </div>
                <div class="form-group">
                    <label>Subject Code (Auto-generated)</label>
                    <input type="text" name="subject_code" id="add_subject_code" required maxlength="20" placeholder="Automatically generated" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Optional description..."></textarea>
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
                    <input type="text" name="subject_name" id="edit_subject_name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Subject Code (Auto-generated)</label>
                    <input type="text" name="subject_code" id="edit_subject_code" required maxlength="20" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
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
