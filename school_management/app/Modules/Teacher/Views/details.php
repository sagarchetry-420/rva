<?php
/**
 * Teacher Details View
 * Variables: $teacher, $pageTitle
 */
$today = date('Y-m-d');
?>
<!-- Load specific stylesheet for details page -->
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/modules/student/details.css'); ?>?v=<?php echo time(); ?>">

<div class="page-header">
    <div>
        <h1><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>View and manage teacher profile</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo moduleUrl('admin', 'teachers'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Teachers</a>
    </div>
</div>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
        </div>
        <h2><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h2>
        <p>@<?php echo htmlspecialchars($teacher['username'] ?? 'N/A'); ?></p>
        
        <div class="profile-badges">
            <?php if (($teacher['status'] ?? 'Active') === 'Active'): ?>
                <span class="badge badge-active">Active Teacher</span>
            <?php else: ?>
                <span class="badge badge-inactive"><?php echo htmlspecialchars($teacher['status'] ?? 'Inactive'); ?></span>
            <?php endif; ?>
        </div>
        
        <hr style="border-top:1px solid #e2e8f0; margin:15px 0;">
        
        <div style="text-align:left;">
            <div style="margin-bottom:10px;"><i class="fas fa-envelope" style="width:20px;color:var(--gray);"></i> <strong>Email:</strong> <?php echo htmlspecialchars($teacher['email'] ?? '—'); ?></div>
            <div style="margin-bottom:10px;"><i class="fas fa-phone" style="width:20px;color:var(--gray);"></i> <strong>Phone:</strong> <?php echo htmlspecialchars($teacher['phone'] ?? '—'); ?></div>
            <div><i class="fas fa-calendar-check" style="width:20px;color:var(--gray);"></i> <strong>Joined:</strong> <?php echo !empty($teacher['joining_date']) ? date('M d, Y', strtotime($teacher['joining_date'])) : '—'; ?></div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="profile-main">
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'overview')"><i class="fas fa-info-circle"></i> Overview</button>
            <button class="tab-btn" onclick="openTab(event, 'actions')"><i class="fas fa-cogs"></i> Actions</button>
        </div>
        
        <!-- Tab: Overview -->
        <div id="overview" class="tab-content active">
            <h3 style="margin-top:0; margin-bottom:20px; color:var(--primary-dark);">Personal Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">First Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($teacher['first_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Last Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($teacher['last_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars($teacher['gender'] ?? '—'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value"><?php echo !empty($teacher['date_of_birth']) ? date('d-m-Y', strtotime($teacher['date_of_birth'])) : '—'; ?></div>
                </div>
            </div>
            
            <h3 style="margin-top:30px; margin-bottom:20px; color:var(--primary-dark);">Academic Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Qualification</div>
                    <div class="detail-value"><?php echo htmlspecialchars($teacher['qualification'] ?? '—'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Subject Specialization</div>
                    <div class="detail-value"><?php echo htmlspecialchars($teacher['subject_specialization'] ?? '—'); ?></div>
                </div>
                <?php if (($teacher['status'] ?? 'Active') === 'Inactive'): ?>
                <div class="detail-item">
                    <div class="detail-label">Leaving Date</div>
                    <div class="detail-value"><?php echo !empty($teacher['leaving_date']) ? date('d-m-Y', strtotime($teacher['leaving_date'])) : '—'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Leaving Reason</div>
                    <div class="detail-value"><?php echo htmlspecialchars($teacher['leaving_reason'] ?? '—'); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tab: Actions -->
        <div id="actions" class="tab-content">
            <h3 style="margin-top:0; margin-bottom:20px; color:var(--primary-dark);">Manage Teacher</h3>
            <div class="action-grid">
                <!-- Edit -->
                <div class="action-card" style="border-left: 4px solid var(--primary);">
                    <h3 style="color:var(--primary);"><i class="fas fa-edit"></i> Edit Profile</h3>
                    <p>Update personal info, contact details, and academic information.</p>
                    <button class="btn btn-sm btn-primary" onclick="openEditModal()">Update Info</button>
                </div>
                
                <?php $isInactive = ($teacher['status'] ?? 'Active') === 'Inactive'; ?>
                
                <!-- Deactivate / Reactivate -->
                <?php if (!$isInactive): ?>
                <div class="action-card" style="border-left: 4px solid var(--warning);">
                    <h3 style="color:var(--warning);"><i class="fas fa-user-times"></i> Deactivate Teacher</h3>
                    <p>Mark teacher as resigned or terminated to disable their account.</p>
                    <button class="btn btn-sm btn-warning" onclick="openModal('deactivateModal')">Deactivate</button>
                </div>
                <?php else: ?>
                <div class="action-card" style="border-left: 4px solid var(--success);">
                    <h3 style="color:var(--success);"><i class="fas fa-user-check"></i> Reactivate Teacher</h3>
                    <p>Restore teacher's active status and enable their login access.</p>
                    <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>" onsubmit="return confirm('Reactivate this teacher?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reactivate">
                        <input type="hidden" name="origin" value="details">
                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success">Reactivate</button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Delete -->
                <div class="action-card" style="border-left: 4px solid var(--danger);">
                    <h3 style="color:var(--danger);"><i class="fas fa-trash"></i> Delete Teacher</h3>
                    <p>Permanently remove this teacher. This action cannot be undone.</p>
                    <form method="POST" action="<?php echo moduleUrl('admin', 'teachers'); ?>" onsubmit="return promptTeacherDelete(event, '<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="origin" value="details">
                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
                        <input type="hidden" name="confirm_teacher_name" id="confirm_teacher_name" value="">
                        <button type="submit" class="btn btn-sm btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->

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
            <input type="hidden" name="origin" value="details">
            <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required pattern="[a-zA-Z\s]+" maxlength="50" value="<?php echo htmlspecialchars($teacher['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required pattern="[a-zA-Z\s]+" maxlength="50" value="<?php echo htmlspecialchars($teacher['last_name']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" max="<?php echo $today; ?>" value="<?php echo htmlspecialchars($teacher['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="Male" <?php echo ($teacher['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($teacher['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($teacher['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required maxlength="150" value="<?php echo htmlspecialchars($teacher['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" pattern="[0-9]{10}" title="Exactly 10 digits" value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" maxlength="150" value="<?php echo htmlspecialchars($teacher['qualification'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Subject Specialization</label>
                        <input type="text" name="subject_specialization" maxlength="150" value="<?php echo htmlspecialchars($teacher['subject_specialization'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Joining Date <i class="fas fa-lock" style="font-size: 11px; color: var(--gray); margin-left: 4px;" title="This field is locked and cannot be edited"></i></label>
                    <input type="date" value="<?php echo htmlspecialchars($teacher['joining_date'] ?? ''); ?>" disabled style="background-color: #f5f5f5; cursor: not-allowed; border-color: #ccc;">
                    <input type="hidden" name="joining_date" value="<?php echo htmlspecialchars($teacher['joining_date'] ?? ''); ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
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
            <input type="hidden" name="origin" value="details">
            <input type="hidden" name="teacher_id" value="<?php echo $teacher['teacher_id']; ?>">
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
function openTab(evt, tabName) {
    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
    
    // Remember the active tab for page reloads
    localStorage.setItem('activeTeacherTab', tabName);
}

// Set flag when any form on this page is submitted
document.addEventListener('submit', function() {
    sessionStorage.setItem('teacherDetailsFormSubmitted', 'true');
});

// Restore active tab ONLY if returning from a form submission
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeTeacherTab');
    const wasSubmitted = sessionStorage.getItem('teacherDetailsFormSubmitted');
    
    if (activeTab && wasSubmitted === 'true') {
        const tabBtn = document.querySelector(`.tab-btn[onclick*="'${activeTab}'"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    }
    
    // Clear the flag so a fresh visit defaults to Overview
    sessionStorage.removeItem('teacherDetailsFormSubmitted');
});

function openEditModal() {
    openModal('editModal');
}

function promptTeacherDelete(event, expectedValue) {
    const userInput = prompt(`WARNING: You are about to permanently delete this teacher.\n\nTo confirm, please type the teacher's full name exactly: ${expectedValue}`);
    
    if (userInput === null) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
    }
    
    const sanitizedInput = userInput.trim().toLowerCase();
    const sanitizedExpected = expectedValue.trim().toLowerCase();
    
    if (sanitizedInput !== sanitizedExpected) {
        alert("Confirmation text did not match. Deletion cancelled.");
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
    }
    
    // Set the hidden field so the server can validate it
    document.getElementById('confirm_teacher_name').value = userInput;
    return true;
}
</script>
