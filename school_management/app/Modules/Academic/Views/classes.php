<?php
/**
 * Classes Management View (Admin)
 * Variables: $classes, $teachers, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-chalkboard"></i> Classes Management</h1>
        <p>Manage all academic classes and sections</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Class</button>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Section</th>
                <th>Class Teacher</th>
                <th>Admission Fee</th>
                <th>Exam Fee</th>
                <th class="actions-cell">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($classes)): ?>
            <?php foreach ($classes as $c): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($c['class_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($c['section']); ?></td>
                <td>
                    <?php 
                        if ($c['first_name']) {
                            echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
                        } else {
                            echo '<span style="color:var(--gray);font-style:italic;">Not Assigned</span>';
                        }
                    ?>
                </td>
                <td><?php echo formatMoney($c['admission_fee'] ?? 0); ?></td>
                <td><?php echo formatMoney($c['exam_fee'] ?? 0); ?></td>
                <td class="actions-cell">
                    <button class="btn btn-sm btn-primary" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($c)); ?>)'><i class="fas fa-edit"></i> Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-door-closed"></i></div><p>No classes found.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Add New Class</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'classes'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>Class Name *</label>
                    <input type="text" name="class_name" required maxlength="50" pattern="^[a-zA-Z0-9\s]+$" title="Only letters, numbers, and spaces are allowed." placeholder="e.g. Class 10">
                </div>
                <div class="form-group">
                    <label>Section *</label>
                    <input type="text" name="section" required maxlength="10" pattern="^[a-zA-Z0-9]+$" title="Only letters and numbers are allowed." placeholder="e.g. A">
                </div>
                <div class="form-group">
                    <label>Class Teacher</label>
                    <select name="class_teacher_id">
                        <option value="">-- No Class Teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['teacher_id']; ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row" style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Admission Fee</label>
                        <input type="number" step="0.01" min="0" name="admission_fee" placeholder="0.00">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Exam Fee</label>
                        <input type="number" step="0.01" min="0" name="exam_fee" placeholder="0.00">
                    </div>
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
            <h2>Edit Class</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'classes'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="class_id" id="edit_class_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Class Name *</label>
                    <input type="text" name="class_name" id="edit_class_name" required maxlength="50" pattern="^[a-zA-Z0-9\s]+$" title="Only letters, numbers, and spaces are allowed.">
                </div>
                <div class="form-group">
                    <label>Section *</label>
                    <input type="text" name="section" id="edit_section" required maxlength="10" pattern="^[a-zA-Z0-9]+$" title="Only letters and numbers are allowed.">
                </div>
                <div class="form-group">
                    <label>Class Teacher</label>
                    <select name="class_teacher_id" id="edit_class_teacher_id">
                        <option value="">-- No Class Teacher --</option>
                        <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo $t['teacher_id']; ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row" style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Admission Fee</label>
                        <input type="number" step="0.01" min="0" name="admission_fee" id="edit_admission_fee" placeholder="0.00">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Exam Fee</label>
                        <input type="number" step="0.01" min="0" name="exam_fee" id="edit_exam_fee" placeholder="0.00">
                    </div>
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
function openEditModal(c) {
    document.getElementById('edit_class_id').value = c.class_id;
    document.getElementById('edit_class_name').value = c.class_name;
    document.getElementById('edit_section').value = c.section;
    document.getElementById('edit_class_teacher_id').value = c.class_teacher_id || '';
    document.getElementById('edit_admission_fee').value = c.admission_fee || '0.00';
    document.getElementById('edit_exam_fee').value = c.exam_fee || '0.00';
    openModal('editModal');
}
</script>
