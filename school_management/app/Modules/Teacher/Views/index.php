<?php
/**
 * Teachers Management View (Admin)
 * Variables: $teachers, $pageTitle
 */
$today = date('Y-m-d');
?>
<style>
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
    <form id="exportForm" method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>" style="display:flex; gap:8px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" id="exportAction" value="export_csv">
        <?php if (!empty($currentStatus)): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($currentStatus); ?>">
        <?php endif; ?>
        <?php if (!empty($searchQuery)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Download CSV</button>
        <button type="button" class="btn btn-danger" onclick="submitPdfExport()"><i class="fas fa-file-pdf"></i> Download PDF</button>
    </form>
</div>

<!-- Teachers Table -->
<div class="table-container" id="printableTable">
    <div class="table-header">
        <h2 style="display: flex; align-items: center;">All Teachers <span style="background-color: #800000; color: white; border-radius: 16px; min-width: 28px; height: 28px; padding: 0 8px; font-size: 15px; font-weight: 600; margin-left: 10px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(128,0,0,0.25);"><?php echo $pagination['total'] ?? count($teachers); ?></span></h2>
        <form method="GET" action="<?php echo moduleUrl('admin', 'teachers'); ?>" class="search-box no-print" data-html2canvas-ignore="true" style="margin: 0; display: flex;">
            <input type="hidden" name="module" value="admin">
            <input type="hidden" name="action" value="teachers">
            <?php if (!empty($currentStatus)): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($currentStatus); ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search teachers..." 
                   pattern="[a-zA-Z0-9\s@\.]+" title="Letters, numbers, @ and dots allowed"
                   value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
            <button type="submit" class="btn btn-primary" style="margin-left: 5px; padding: 6px 12px; border-radius: 4px;"><i class="fas fa-search"></i></button>
        </form>
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
                <td class="no-print">
                    <a href="<?php echo moduleUrl('admin', 'teacher_details', ['id' => $t['teacher_id']]); ?>" class="btn btn-sm btn-primary" style="border-radius: 4px;"><i class="fas fa-eye"></i> View Profile</a>
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
                        <input type="email" name="email" required maxlength="150" placeholder="Enter valid email address.">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" pattern="[0-9]{10}" title="Exactly 10 digits" placeholder="Mobile/Phone Number">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" maxlength="150" pattern="[a-zA-Z0-9\s\.,\-&]+" title="Only letters, numbers, spaces, and basic punctuation allowed" placeholder="e.g. M.Sc, B.Ed">
                    </div>
                    <div class="form-group">
                        <label>Subject Specialization</label>
                        <input type="text" name="subject_specialization" maxlength="150" pattern="[a-zA-Z0-9\s\.,\-&]+" title="Only letters, numbers, spaces, and basic punctuation allowed" placeholder="e.g. Mathematics">
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



<script>


function submitPdfExport() {
    const form = document.getElementById('exportForm');
    document.getElementById('exportAction').value = 'export_pdf';
    form.submit();
    // Reset back to CSV
    setTimeout(() => {
        document.getElementById('exportAction').value = 'export_csv';
    }, 100);
}
</script>
