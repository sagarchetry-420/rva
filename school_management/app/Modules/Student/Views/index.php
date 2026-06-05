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
    <form id="exportForm" method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" class="no-auto-validate" style="display:flex; gap:10px; align-items: center;" onsubmit="return validateDownload()">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="export_csv">
        <input type="hidden" name="filter_class" value="<?php echo $filterClass; ?>" id="export_filter_class">
        <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Download CSV</button>
        <button type="button" class="btn btn-danger" onclick="submitPdfExport()"><i class="fas fa-file-pdf"></i> Download PDF</button>
    </form>
</div>

<!-- Students Table -->
<div class="table-container" id="printableTable">
    <div class="table-header">
        <h2 style="display: flex; align-items: center;">All Students <span style="background-color: #800000; color: white; border-radius: 16px; min-width: 28px; height: 28px; padding: 0 8px; font-size: 15px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(128,0,0,0.25);"><?php echo $pagination['total'] ?? count($students); ?></span></h2>
        <form method="GET" action="<?php echo moduleUrl('admin', 'students'); ?>" class="search-box no-print" data-html2canvas-ignore="true" style="margin: 0; display: flex;">
            <input type="hidden" name="module" value="admin">
            <input type="hidden" name="action" value="students">
            <?php if ($filterClass): ?>
                <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search students..." 
                   pattern="[a-zA-Z0-9\s]+" title="Only letters and numbers allowed"
                   oninput="this.value = this.value.replace(/[^a-zA-Z0-9\s]/g, '')"
                   value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
            <button type="submit" class="btn btn-primary" style="margin-left: 5px; padding: 6px 12px; border-radius: 4px;"><i class="fas fa-search"></i></button>
        </form>
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
                <th class="no-print" data-html2canvas-ignore="true">Action</th>
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
                        <?php 
                        $statusText = 'Left School';
                        $statusColor = '#ef4444';
                        $reason = $s['leaving_reason'] ?? '';
                        if (stripos($reason, 'Pass') !== false) {
                            $statusText = 'Passed Out';
                            $statusColor = '#3b82f6';
                        }
                        ?>
                        <span class="badge" style="background:<?php echo $statusColor; ?>; color:white; padding:2px 6px; font-size:10px; border-radius:4px; margin-left:5px;"><?php echo $statusText; ?></span>
                    <?php endif; ?>
                    <br><small style="color:var(--gray)">@<?php echo htmlspecialchars($s['username'] ?? 'N/A'); ?></small>
                </td>
                <td><?php echo htmlspecialchars(($s['class_name'] ?? '') . ' ' . ($s['section'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($s['gender'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($s['parent_name'] ?? ''); ?></td>
                <td class="no-print" data-html2canvas-ignore="true">
                    <a href="<?php echo moduleUrl('admin', 'student_details', ['id' => $s['student_id']]); ?>" class="btn btn-sm btn-primary" style="border-radius: 4px;"><i class="fas fa-eye"></i> View Profile</a>
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
                        <input type="text" name="parent_name" pattern="[a-zA-Z\s.]+" title="Letters, spaces, and dots only" maxlength="100">
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

<!-- Import Modal -->
<div id="importModal" class="modal">
    <div class="modal-content" style="max-width: 550px; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; flex-direction: column; max-height: 90vh;">
        <div class="modal-header" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px 24px; flex-shrink: 0;">
            <h2 style="margin: 0; color: #1e293b; font-size: 20px;"><i class="fas fa-file-import" style="color: #3b82f6; margin-right: 8px;"></i> Bulk Import Students</h2>
            <span class="close" onclick="closeModal('importModal')" style="font-size: 24px; color: #64748b; cursor: pointer;">&times;</span>
        </div>
        <form id="importForm" method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" enctype="multipart/form-data" onsubmit="return validateImportFile()" style="display: flex; flex-direction: column; overflow: hidden;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="import_csv">
            <div class="modal-body" style="padding: 24px; overflow-y: auto; flex-grow: 1;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Assign to Class *</label>
                    <select name="class_id" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #fff;">
                        <option value="">-- Select Destination Class --</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . ($c['section'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; color: #475569; display: block; margin-bottom: 8px;">Upload Data File *</label>
                    <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 30px; text-align: center; background-color: #f8fafc; transition: all 0.3s;" id="dropZone">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94a3b8; margin-bottom: 10px;"></i>
                        <p style="margin: 0 0 10px 0; color: #64748b; font-size: 14px;">Drag and drop your .csv file here or click to browse</p>
                        <input type="file" name="csv_file" id="csv_file_input" accept=".csv" required style="display: block; margin: 0 auto; color: #475569;">
                    </div>
                </div>

                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="color: #1e40af; font-size: 14px;"><i class="fas fa-info-circle"></i> Use the official template for accurate imports</span>
                    <button type="button" onclick="document.getElementById('downloadTemplateForm').submit();" class="btn btn-sm" style="background-color: #fff; color: #2563eb; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-download"></i> Template
                    </button>
                </div>

                <div class="form-group" style="background-color: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <input type="checkbox" name="send_emails" id="send_emails" value="1" checked style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="send_emails" style="margin: 0; font-weight: 600; color: #1e293b; cursor: pointer;">Automatically generate and email login credentials</label>
                    </div>
                    <div style="padding-left: 28px;">
                        <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            <i class="fas fa-exclamation-triangle" style="color: #eab308; margin-right: 4px;"></i>
                            <strong>Helpful Tip:</strong> To make sure every student successfully receives their welcome email, please only upload a maximum of <strong>200 to 300 students per day</strong>. If you try to add 1,000 students all at once, email companies (like Gmail or Yahoo) will think you are sending spam and will block the emails.
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn" style="background-color: #fff; border: 1px solid #cbd5e1; color: #475569;" onclick="closeModal('importModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="importSubmitBtn"><i class="fas fa-upload"></i> Start Import</button>
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

function validateDownload() {
    const filterClass = document.getElementById('export_filter_class').value;
    if (!filterClass || filterClass === "0") {
        alert("Please select a specific Class from the dropdown before downloading.");
        return false;
    }
    return true;
}

function submitPdfExport() {
    if (!validateDownload()) return;
    const form = document.getElementById('exportForm');
    const originalAction = form.elements['action'].value;
    form.elements['action'].value = 'export_pdf';
    form.submit();
    // Revert back so CSV works next time
    setTimeout(() => { form.elements['action'].value = originalAction; }, 100);
}

function validateImportFile() {
    const fileInput = document.getElementById('csv_file_input');
    const file = fileInput.files[0];
    
    if (!file) {
        alert("Please select a file to import.");
        return false;
    }
    
    if (!file.name.toLowerCase().endsWith('.csv')) {
        alert("Security Block: Only strict .csv files are allowed to be imported.");
        fileInput.value = ''; // clear
        return false;
    }

    // Replace footer with progress bar UI
    const footer = document.querySelector('#importModal .modal-footer');
    footer.style.display = 'none';

    const progressContainer = document.createElement('div');
    progressContainer.style.padding = '20px 24px';
    progressContainer.style.backgroundColor = '#f8fafc';
    progressContainer.style.borderTop = '1px solid #e2e8f0';
    progressContainer.style.textAlign = 'center';

    progressContainer.innerHTML = `
        <p style="margin: 0 0 12px 0; font-weight: bold; color: #1e293b; font-size: 15px;">
            <i class="fas fa-spinner fa-spin" style="color: #800000; margin-right: 5px;"></i> Uploading & Processing Students...
        </p>
        <div style="width: 100%; height: 12px; background-color: #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 12px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
            <div id="uploadProgressBar" style="width: 0%; height: 100%; background-color: #800000; transition: width 0.5s ease-out; border-radius: 6px;"></div>
        </div>
        <p style="margin: 0; color: #dc2626; font-size: 13px; font-weight: 600;">
            <i class="fas fa-exclamation-circle"></i> Please DO NOT click anywhere, refresh, or go back until finished!
        </p>
    `;

    document.getElementById('importForm').appendChild(progressContainer);

    // Animate progress bar simulating upload & email sending
    const progressBar = document.getElementById('uploadProgressBar');
    let progress = 0;
    setInterval(() => {
        // Asymptotically approach 95% while waiting for server
        progress += (95 - progress) * 0.05;
        progressBar.style.width = progress + '%';
    }, 500);

    return true;
}
</script>
