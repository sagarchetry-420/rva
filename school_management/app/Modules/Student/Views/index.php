<?php
/**
 * Students Management View (Admin)
 * Variables: $students, $classes, $filterClass, $pageTitle
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
        <h1><i class="fas fa-user-graduate"></i> Students Management</h1>
        <p>Manage all student records</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Student</button>
        <button class="btn btn-secondary" onclick="openModal('importModal')"><i class="fas fa-upload"></i> Bulk Import</button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" style="display:flex; gap:15px; align-items:center; flex: 1; min-width: 200px;">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="action" value="students">
        <div class="filter-group" style="flex: 1; max-width: 300px;">
            <select name="class_id" onchange="this.form.submit()">
                <option value="">-- All Classes --</option>
                <?php foreach ($classes as $c): ?>
                <option value="<?php echo $c['class_id']; ?>" <?php echo $filterClass == $c['class_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['class_name'] . ' ' . ($c['section'] ?? '')); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" class="no-auto-validate" style="display:flex; gap:10px; align-items: center;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="export_csv">
        <input type="hidden" name="filter_class" value="<?php echo $filterClass; ?>">
        <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Download CSV</button>
        <button type="button" class="btn btn-danger" onclick="downloadPDF()"><i class="fas fa-file-pdf"></i> Download PDF</button>
    </form>
</div>

<!-- Students Table -->
<div class="table-container" id="printableTable">
    <div class="table-header">
        <h2>All Students (<?php echo count($students); ?>)</h2>
        <div class="search-box no-print" data-html2canvas-ignore="true">
            <input type="text" id="searchInput" placeholder="Search students..." onkeyup="searchTable('searchInput','dataTable')">
        </div>
    </div>
    <table class="data-table" id="dataTable">
        <thead>
            <tr>
                <th>Roll No</th>
                <th>Name</th>
                <th>Class</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Parent</th>
                <th class="no-print" data-html2canvas-ignore="true">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($students)): ?>
            <?php foreach ($students as $s): ?>
            <tr>
                <td><?php echo htmlspecialchars($s['roll_number'] ?? ''); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?></strong>
                    <?php if (!empty($s['leaving_date'])): ?>
                        <span class="badge" style="background:#ef4444; color:white; padding:2px 6px; font-size:10px; border-radius:4px; margin-left:5px;">Left School</span>
                    <?php endif; ?>
                    <br><small style="color:var(--gray)">@<?php echo htmlspecialchars($s['username'] ?? 'N/A'); ?></small>
                </td>
                <td><?php echo htmlspecialchars(($s['class_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($s['gender'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($s['parent_name'] ?? ''); ?></td>
                <td class="actions-cell no-print" data-html2canvas-ignore="true" style="overflow: visible; white-space: nowrap;">
                    <div style="display: inline-flex; align-items: center; gap: 5px;">
                        <button class="btn btn-sm btn-info" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)' style="border-radius: 4px;"><i class="fas fa-edit"></i> Edit</button>
                        <div class="action-menu">
                            <button class="action-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                            <div class="action-menu-content">
                                <button onclick='viewDetails(<?php echo htmlspecialchars(json_encode($s)); ?>)'><i class="fas fa-eye" style="width: 20px;"></i> View Details</button>
                            <button onclick='openServicesModal(<?php echo $s['student_id']; ?>)'><i class="fas fa-hand-holding-usd" style="width: 20px;"></i> Services</button>
                            <button style="color: #f97316;" onclick='openWithdrawModal(<?php echo $s['student_id']; ?>)'><i class="fas fa-sign-out-alt" style="width: 20px;"></i> TC / Withdraw</button>
                            <hr style="margin: 5px 0; border: none; border-top: 1px solid var(--border);">
                            <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" onsubmit="return confirmDelete('Delete this student? This action cannot be undone.')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="student_id" value="<?php echo $s['student_id']; ?>">
                                <button type="submit" class="text-danger"><i class="fas fa-trash" style="width: 20px;"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="fas fa-user-graduate"></i></div><p>No students found.</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (isset($pagination)): ?>
        <div class="no-print" data-html2canvas-ignore="true">
            <?php echo renderPagination($pagination); ?>
            <div style="text-align: center; margin-top: 10px; color: var(--gray); font-size: 13px;">
                Showing page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['pages']; ?> (Total: <?php echo $pagination['total']; ?> students)
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Student Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Student</h2>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="roll_number" id="add_roll_number">
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
                <div class="form-group">
                    <label>Class *</label>
                    <select name="class_id" required id="addClassSelect" onchange="autoGenerateRoll('add')">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . ($c['section'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required maxlength="150" placeholder="Credentials will be sent here">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" pattern="[0-9]{10}" maxlength="10" title="10 digits" placeholder="10 digits">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent Name</label>
                        <input type="text" name="parent_name" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone" pattern="[0-9]{10}" maxlength="10" title="10 digits">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Register Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Student</h2>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_student_id">
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
                        <label>Class *</label>
                        <select name="class_id" id="edit_class_id" required>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . ($c['section'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Roll Number</label>
                        <input type="text" name="roll_number" id="edit_roll_number" maxlength="20">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" id="edit_email" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="edit_phone" pattern="[0-9]{10}" maxlength="10">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent Name</label>
                        <input type="text" name="parent_name" id="edit_parent_name" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone" id="edit_parent_phone" pattern="[0-9]{10}" maxlength="10">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- View Student Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Student Details</h2>
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div class="modal-body" id="viewModalBody"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Bulk Import Students</h2>
            <span class="close" onclick="closeModal('importModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="import_csv">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Assign to Class *</label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . ($c['section'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>CSV File *</label>
                    <input type="file" name="csv_file" accept=".csv" required style="padding: 10px 0;">
                    <small>
                        <button type="button" onclick="document.getElementById('downloadTemplateForm').submit();" style="background:none;border:none;color:#3b82f6;text-decoration:underline;cursor:pointer;padding:0;font-size:inherit;">
                            <i class="fas fa-download"></i> Download CSV Template
                        </button>
                    </small>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="send_emails" id="send_emails" value="1" checked>
                    <label for="send_emails" style="margin: 0;">Send login credentials via email automatically</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('importModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Services Modal -->
<div id="servicesModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Assign Services</h2>
            <span class="close" onclick="closeModal('servicesModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="assign_services">
            <input type="hidden" name="student_id" id="services_student_id">
            <div class="modal-body">
                <p style="margin-bottom:15px; color:var(--gray);">Select the services this student will be enrolled in. Note: Newly assigned services will automatically generate an invoice.</p>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $srv): ?>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px; border:1px solid #e5e7eb; border-radius:5px;">
                            <input type="checkbox" name="service_ids[]" value="<?php echo $srv['service_id']; ?>">
                            <span><?php echo htmlspecialchars($srv['service_name']); ?> <br><small style="color:var(--gray);"><?php echo formatMoney($srv['fee_amount'] ?? 0); ?></small></span>
                        </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:var(--danger);">No active services found. Add services in the Fee module first.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('servicesModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Assignments</button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Student Modal -->
<div id="withdrawModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>TC / Withdraw Student</h2>
            <span class="close" onclick="closeModal('withdrawModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="withdraw">
            <input type="hidden" name="student_id" id="withdraw_student_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Leaving Date *</label>
                    <input type="date" name="leaving_date" value="<?php echo date('Y-m-d'); ?>" required max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <select name="leaving_reason" required>
                        <option value="">Select Reason</option>
                        <option value="TC Issued">TC Issued</option>
                        <option value="Withdrawn">Withdrawn</option>
                        <option value="Expelled">Expelled</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <p style="font-size:12px; color:var(--gray); margin-top:10px;">Warning: Withdrawing a student will disable their login. Historical records (fees, exams) will be preserved.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('withdrawModal')">Cancel</button>
                <button type="submit" class="btn btn-danger" style="background:#f97316;"><i class="fas fa-sign-out-alt"></i> Withdraw Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Form for CSV Template Download (Bypasses GET routing 404) -->
<form id="downloadTemplateForm" method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" class="no-auto-validate" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="download_template">
</form>

<script>
function openWithdrawModal(studentId) {
    document.getElementById('withdraw_student_id').value = studentId;
    openModal('withdrawModal');
}

function openEditModal(s) {
    document.getElementById('edit_student_id').value = s.student_id;
    document.getElementById('edit_first_name').value = s.first_name || '';
    document.getElementById('edit_last_name').value = s.last_name || '';
    document.getElementById('edit_dob').value = s.date_of_birth || '';
    document.getElementById('edit_gender').value = s.gender || 'Male';
    document.getElementById('edit_class_id').value = s.current_class_id || '';
    document.getElementById('edit_roll_number').value = s.roll_number || '';
    document.getElementById('edit_email').value = s.email || '';
    document.getElementById('edit_phone').value = s.phone || '';
    document.getElementById('edit_parent_name').value = s.parent_name || '';
    document.getElementById('edit_parent_phone').value = s.parent_phone || '';
    openModal('editModal');
}

function openServicesModal(studentId) {
    document.getElementById('services_student_id').value = studentId;
    // Note: We don't have the assigned services loaded in this quick view, 
    // ideally an AJAX request would fetch checked services first. But for now, user re-checks them.
    openModal('servicesModal');
}

function viewDetails(s) {
    document.getElementById('viewModalBody').innerHTML = `
        <table class="detail-table" style="width:100%;border-collapse:collapse;">
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;width:35%;">Name</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.first_name || ''} ${s.last_name || ''}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Username</th><td style="padding:10px;border:1px solid #e5e7eb;">@${s.username || 'N/A'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Roll Number</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.roll_number || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Class</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.class_name || ''} ${s.section || ''}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Gender</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.gender || ''}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Date of Birth</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.date_of_birth || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Email</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.email || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Phone</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.phone || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Parent Name</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.parent_name || '—'}</td></tr>
            <tr><th style="text-align:left;padding:10px;background:#f9fafb;border:1px solid #e5e7eb;">Parent Phone</th><td style="padding:10px;border:1px solid #e5e7eb;">${s.parent_phone || '—'}</td></tr>
        </table>`;
    openModal('viewModal');
}

function autoGenerateRoll(prefix) {
    const classSelect = document.getElementById(prefix + 'ClassSelect');
    if (!classSelect || !classSelect.value) return;

    fetch('<?php echo moduleUrl("admin", "students"); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'action=generate_roll_number&class_id=' + classSelect.value + '&_csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(r => r.json())
    .then(d => {
        if (d.roll_number) document.getElementById(prefix + '_roll_number').value = d.roll_number;
    });
}

function downloadPDF() {
    const el = document.getElementById('printableTable');
    const opt = { margin: 0.5, filename: 'students_list.pdf', image: {type:'jpeg',quality:0.98}, html2canvas: {scale:2}, jsPDF: {unit:'in',format:'a4',orientation:'landscape'} };
    html2pdf().set(opt).from(el).save();
}
</script>
