<?php
/**
 * Assignment View (Admin)
 * Variables: $classes, $subjects, $teachers, $assignments, $filterClass, $session, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-project-diagram"></i> Class & Subject Assignments</h1>
        <p>Assign subjects and specific teachers to classes</p>
    </div>
</div>

<?php if (!$session): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> No active academic session found. You must set an active session before assigning subjects.
    </div>
<?php else: ?>
    <div style="background:var(--primary-light); color:white; padding:10px 15px; border-radius:8px; margin-bottom:20px; font-weight:bold;">
        <i class="fas fa-calendar-alt"></i> Active Session: <?php echo htmlspecialchars($session['session_name']); ?>
    </div>

    <div class="row" style="display:flex; gap:20px;">
        
        <!-- Left Side: Selection & Add -->
        <div class="col-md-4" style="flex:1;">
            <div class="form-card" style="margin-bottom: 20px;">
                <h3>Select Class</h3>
                <form method="GET">
                    <input type="hidden" name="module" value="admin">
                    <input type="hidden" name="action" value="assignments">
                    <select name="class_id" class="form-control" onchange="this.form.submit()" style="width:100%; padding:10px; border:1px solid var(--border);">
                        <option value="">-- Choose a Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class_id']; ?>" <?php echo $filterClass == $c['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if ($filterClass): ?>
            <div class="form-card">
                <h3>Assign New Subject</h3>
                <form method="POST" action="<?php echo moduleUrl('admin', 'assignments'); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Subject *</label>
                        <select name="subject_id" required style="width:100%; padding:10px; border:1px solid var(--border);">
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['subject_id']; ?>"><?php echo htmlspecialchars($s['subject_name'] . ' (' . $s['subject_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Assigned Teacher</label>
                        <select name="teacher_id" style="width:100%; padding:10px; border:1px solid var(--border);">
                            <option value="">-- No Teacher / TBD --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['teacher_id']); ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; padding:10px;"><i class="fas fa-link"></i> Assign to Class</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Side: Assigned List -->
        <div class="col-md-8" style="flex:2;">
            <div class="table-container">
                <div class="table-header">
                    <h2>Current Assignments<?php 
                        if ($filterClass) {
                            foreach ($classes as $c) {
                                if ($c['class_id'] == $filterClass) {
                                    echo ' - ' . htmlspecialchars($c['class_name'] . ' ' . $c['section']);
                                    break;
                                }
                            }
                        }
                    ?></h2>
                </div>
                
                <?php if (!$filterClass): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-hand-point-left"></i></div>
                        <p>Select a class from the left to view and manage subjects.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Subject Code</th>
                                <th>Teacher</th>
                                <th class="actions-cell">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($assignments)): ?>
                            <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($a['subject_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($a['subject_code']); ?></td>
                                <td>
                                    <?php 
                                        if ($a['first_name']) {
                                            echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']);
                                            if (($a['status'] ?? 'Active') === 'Inactive') {
                                                echo ' <span class="badge" style="background:#ef4444; color:white; padding:2px 6px; font-size:10px; border-radius:4px;">Left</span>';
                                            }
                                        } else {
                                            echo '<span style="color:var(--gray);font-style:italic;">Unassigned</span>';
                                        }
                                    ?>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn btn-sm btn-info" onclick='openEditAssignmentModal(<?php echo htmlspecialchars(json_encode([
                                        "id" => $a["id"],
                                        "subject_name" => $a["subject_name"],
                                        "teacher_id" => $a["teacher_id"]
                                    ])); ?>)'><i class="fas fa-edit"></i> Edit Teacher</button>
                                    
                                    <form method="POST" action="<?php echo moduleUrl('admin', 'assignments'); ?>" style="display:inline" onsubmit="return confirm('Are you sure you want to remove this subject from the class? This action cannot be undone.');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                        <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-unlink"></i> Remove</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-folder-open"></i></div><p>No subjects assigned to this class yet.</p></div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Edit Assignment Modal -->
<div id="editAssignmentModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Edit Assigned Teacher</h2>
            <span class="close" onclick="closeModal('editAssignmentModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'assignments'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_teacher">
            <input type="hidden" name="assignment_id" id="edit_assignment_id">
            <input type="hidden" name="class_id" value="<?php echo $filterClass ?? ''; ?>">
            
            <div class="modal-body">
                <p><strong>Subject:</strong> <span id="edit_assignment_subject"></span></p>
                <div class="form-group" style="margin-top: 15px;">
                    <label>Assigned Teacher</label>
                    <select name="teacher_id" id="edit_assignment_teacher" style="width:100%; padding:10px; border:1px solid var(--border);">
                        <option value="">-- No Teacher / TBD --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['teacher_id']); ?>"><?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editAssignmentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditAssignmentModal(data) {
    document.getElementById('edit_assignment_id').value = data.id;
    document.getElementById('edit_assignment_subject').textContent = data.subject_name;
    document.getElementById('edit_assignment_teacher').value = data.teacher_id || '';
    openModal('editAssignmentModal');
}
</script>
