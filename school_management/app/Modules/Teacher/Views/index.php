<?php
/**
 * Teachers Management View (Admin)
 * Variables: $teachers, $pageTitle
 */
$today = date('Y-m-d');
?>
<style>
.action-menu { position: relative; display: inline-flex; align-items: center; gap: 5px; }
.action-menu-btn { background: none; border: none; cursor: pointer; font-size: 18px; color: var(--gray); padding: 5px 10px; border-radius: 4px; }
.action-menu-btn:hover { background: #f1f5f9; color: var(--primary); }
.action-menu-content { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 150px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border-radius: 6px; overflow: hidden; border: 1px solid var(--border); }
.action-menu-content a, .action-menu-content button { display: block; width: 100%; text-align: left; padding: 10px 15px; text-decoration: none; color: var(--text); border: none; background: none; cursor: pointer; font-size: 13px; }
.action-menu-content a:hover, .action-menu-content button:hover { background-color: #f8fafc; color: var(--primary); }
.action-menu-content .text-danger:hover { color: #ef4444; }
.action-menu:hover .action-menu-content { display: block; }
tbody tr:last-child .action-menu-content, 
tbody tr:nth-last-child(2) .action-menu-content,
tbody tr:nth-last-child(3) .action-menu-content { top: auto; bottom: 100%; }
</style>
<div class="page-header">
    <div>
        <h1><i class="fas fa-chalkboard-teacher"></i> Teachers Management</h1>
        <p>Manage all teaching staff records</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Teacher</button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar" style="display:flex; justify-content:space-between; align-items:center;">
    <div class="status-tabs" style="display:flex; gap:10px;">
        <a href="<?php echo moduleUrl('admin', 'teachers'); ?>?status=Active" class="btn <?php echo ($currentStatus ?? 'Active') === 'Active' ? 'btn-primary' : 'btn-secondary'; ?>">Active</a>
        <a href="<?php echo moduleUrl('admin', 'teachers'); ?>?status=Inactive" class="btn <?php echo ($currentStatus ?? '') === 'Inactive' ? 'btn-primary' : 'btn-secondary'; ?>">Inactive (Left)</a>
        <a href="<?php echo moduleUrl('admin', 'teachers'); ?>?status=all" class="btn <?php echo ($currentStatus ?? '') === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All Teachers</a>
    </div>
    <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>" style="display:flex; gap:8px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="export_csv">
        <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Download CSV</button>
        <button type="button" class="btn btn-danger" onclick="downloadPDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>
    </form>
</div>

<!-- Teachers Table -->
<div class="table-container" id="printableTable">
    <div class="table-header">
        <h2>All Teachers (<?php echo count($teachers); ?>)</h2>
        <div class="search-box no-print">
            <input type="text" id="searchInput" placeholder="Search teachers..." onkeyup="searchTable('searchInput','dataTable')">
        </div>
    </div>
    <table class="data-table" id="dataTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Specialization</th>
                <th>Joining Date</th>
                <th class="no-print">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($teachers)): ?>
            <?php foreach ($teachers as $t): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')); ?></strong>
                    <?php if (($t['status'] ?? 'Active') === 'Inactive'): ?>
                        <span class="badge" style="background:#ef4444; color:white; padding:2px 6px; font-size:10px; border-radius:4px; margin-left:5px;">Inactive</span>
                    <?php endif; ?>
                    <br><small style="color:var(--gray)">@<?php echo htmlspecialchars($t['username'] ?? 'N/A'); ?></small>
                </td>
                <td><?php echo htmlspecialchars($t['gender'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($t['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($t['phone'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($t['subject_specialization'] ?? '—'); ?></td>
                <td><?php echo formatDate($t['joining_date'] ?? null); ?></td>
                <td class="actions-cell no-print" style="overflow: visible; white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                        <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($t)); ?>)' style="border-radius: 4px;"><i class="fas fa-edit"></i> Edit</button>
                        <div class="action-menu">
                            <button class="action-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                            <div class="action-menu-content">
                                <button onclick='viewDetails(<?php echo htmlspecialchars(json_encode($t)); ?>)'><i class="fas fa-eye" style="width: 20px;"></i> View Details</button>
                                <?php if (($t['status'] ?? 'Active') === 'Active'): ?>
                                <button onclick='openDeactivateModal(<?php echo $t['teacher_id']; ?>)'><i class="fas fa-user-times" style="width: 20px;"></i> Deactivate</button>
                            <?php else: ?>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>" onsubmit="return confirm('Reactivate this teacher?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="reactivate">
                                    <input type="hidden" name="teacher_id" value="<?php echo $t['teacher_id']; ?>">
                                    <button type="submit"><i class="fas fa-user-check" style="width: 20px;"></i> Reactivate</button>
                                </form>
                            <?php endif; ?>
                            <hr style="margin: 5px 0; border: none; border-top: 1px solid var(--border);">
                            <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>" onsubmit="return confirmDelete('Delete this teacher? This action cannot be undone.')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="teacher_id" value="<?php echo $t['teacher_id']; ?>">
                                <button type="submit" class="text-danger"><i class="fas fa-trash" style="width: 20px;"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fas fa-chalkboard-teacher"></i></div><p>No teachers found.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (isset($pagination)): ?>
        <div class="no-print" data-html2canvas-ignore="true">
            <?php echo renderPagination($pagination); ?>
            <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> teachers)
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Teacher Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Teacher</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required pattern="[a-zA-Z\s]+" title="Letters and spaces only" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required pattern="[a-zA-Z\s]+" title="Letters and spaces only" maxlength="50">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" max="<?php echo $today; ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required maxlength="150" placeholder="Credentials will be sent here">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" pattern="[0-9]{10}" title="Exactly 10 digits" placeholder="Mobile/Phone Number">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" maxlength="150" placeholder="e.g. M.Sc, B.Ed">
                    </div>
                    <div class="form-group">
                        <label>Subject Specialization</label>
                        <input type="text" name="subject_specialization" maxlength="150" placeholder="e.g. Mathematics">
                    </div>
                </div>
                <div class="form-group">
                    <label>Joining Date</label>
                    <input type="date" name="joining_date" value="<?php echo $today; ?>" min="<?php echo $today; ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register Teacher</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Teacher</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="teacher_id" id="edit_teacher_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" id="edit_first_name" required pattern="[a-zA-Z\s]+" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" id="edit_last_name" required pattern="[a-zA-Z\s]+" maxlength="50">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" id="edit_dob" max="<?php echo $today; ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" id="edit_gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" id="edit_email" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="edit_phone" pattern="[0-9]{10}" title="Exactly 10 digits">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" id="edit_qualification" maxlength="150">
                    </div>
                    <div class="form-group">
                        <label>Subject Specialization</label>
                        <input type="text" name="subject_specialization" id="edit_specialization" maxlength="150">
                    </div>
                </div>
                <div class="form-group">
                    <label>Joining Date</label>
                    <input type="date" name="joining_date" id="edit_joining" min="<?php echo $today; ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- View Teacher Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Teacher Details</h2>
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div class="modal-body" id="viewModalBody"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Deactivate Teacher Modal -->
<div id="deactivateModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Deactivate Teacher</h2>
            <span class="close" onclick="closeModal('deactivateModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="deactivate">
            <input type="hidden" name="teacher_id" id="deactivate_teacher_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Leaving Date *</label>
                    <input type="date" name="leaving_date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <select name="leaving_reason" required>
                        <option value="">Select Reason</option>
                        <option value="Resigned">Resigned</option>
                        <option value="Contract Ended">Contract Ended</option>
                        <option value="Terminated">Terminated</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <p style="font-size:12px; color:var(--gray); margin-top:10px;">If the leaving date is in the future, the teacher will still be able to log in until that date. You must manually reassign their classes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deactivateModal')">Cancel</button>
                <button type="submit" class="btn btn-danger" style="background:#f97316;"><i class="fas fa-user-times"></i> Deactivate Teacher</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDeactivateModal(teacherId) {
    document.getElementById('deactivate_teacher_id').value = teacherId;
    openModal('deactivateModal');
}

function openEditModal(t) {
    document.getElementById('edit_teacher_id').value = t.teacher_id;
    document.getElementById('edit_first_name').value = t.first_name || '';
    document.getElementById('edit_last_name').value = t.last_name || '';
    document.getElementById('edit_dob').value = t.date_of_birth || '';
    document.getElementById('edit_gender').value = t.gender || 'Male';
    document.getElementById('edit_email').value = t.email || '';
    document.getElementById('edit_phone').value = t.phone || '';
    document.getElementById('edit_qualification').value = t.qualification || '';
    document.getElementById('edit_specialization').value = t.subject_specialization || '';
    document.getElementById('edit_joining').value = t.joining_date || '';
    openModal('editModal');
}

function viewDetails(t) {
    document.getElementById('viewModalBody').innerHTML = `
        <table class="detail-table" style="width:100%;border-collapse:collapse;">
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;width:35%;">Name</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.first_name || ''} ${t.last_name || ''}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Username</th><td style="padding:10px;border:1px solid #e5e7eb;">@${t.username || 'N/A'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Gender</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.gender || ''}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Date of Birth</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.date_of_birth || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Email</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.email || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Phone</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.phone || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Qualification</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.qualification || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Specialization</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.subject_specialization || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Joining Date</th><td style="padding:10px;border:1px solid #e5e7eb;">${t.joining_date || '—'}</td></tr>
        </table>`;
    openModal('viewModal');
}

function downloadPDF() {
    // Hide elements we don't want in the PDF
    const noPrintElements = document.querySelectorAll('.no-print');
    noPrintElements.forEach(el => el.style.display = 'none');

    const el = document.getElementById('printableTable');
    const opt = { 
        margin: 0.5, 
        filename: 'teachers_list.pdf', 
        image: {type:'jpeg',quality:0.98}, 
        html2canvas: {scale:2}, 
        jsPDF: {unit:'in',format:'a4',orientation:'landscape'} 
    };
    
    html2pdf().set(opt).from(el).save().then(() => {
        // Restore elements after PDF generation
        noPrintElements.forEach(el => el.style.display = '');
    });
}
</script>
