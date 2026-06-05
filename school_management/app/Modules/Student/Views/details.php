<?php
/**
 * Student Details View
 * Variables: $student, $classes, $services, $assignedServices, $pageTitle
 */
$today = date('Y-m-d');
?>
<!-- Load specific stylesheet for student details page -->
<link rel="stylesheet" href="<?php echo baseUrl('assets/css/modules/student/details.css'); ?>?v=<?php echo time(); ?>">

<div class="page-header">
    <div>
        <h1><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>View and manage student profile</p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="<?php echo moduleUrl('admin', 'students'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Students</a>
    </div>
</div>

<div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
        </div>
        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
        <p>@<?php echo htmlspecialchars($student['username'] ?? 'N/A'); ?></p>
        
        <div class="profile-badges">
            <?php if (empty($student['leaving_date'])): ?>
                <span class="badge badge-active">Active Student</span>
            <?php elseif ($student['leaving_reason'] === 'Passed Out'): ?>
                <span class="badge badge-blue">Passed Out</span>
            <?php else: ?>
                <span class="badge badge-inactive"><?php echo htmlspecialchars($student['leaving_reason'] ?? 'Inactive'); ?></span>
            <?php endif; ?>
        </div>
        
        <hr style="border-top:1px solid #e2e8f0; margin:15px 0;">
        
        <div style="text-align:left;">
            <div style="margin-bottom:10px;"><i class="fas fa-chalkboard-teacher" style="width:20px;color:var(--gray);"></i> <strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . ($student['section'] ?? '')); ?></div>
            <div style="margin-bottom:10px;"><i class="fas fa-id-badge" style="width:20px;color:var(--gray);"></i> <strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_number'] ?? '—'); ?></div>
            <div><i class="fas fa-envelope" style="width:20px;color:var(--gray);"></i> <strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? '—'); ?></div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="profile-main">
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'overview')"><i class="fas fa-info-circle"></i> Overview</button>
            <button class="tab-btn" onclick="openTab(event, 'services')"><i class="fas fa-concierge-bell"></i> Services</button>
            <button class="tab-btn" onclick="openTab(event, 'actions')"><i class="fas fa-cogs"></i> Actions</button>
        </div>
        
        <!-- Tab: Overview -->
        <div id="overview" class="tab-content active">
            <h3 style="margin-top:0; margin-bottom:20px; color:var(--primary-dark);">Personal Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">First Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['first_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Last Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['last_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['gender'] ?? '—'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date of Birth</div>
                    <div class="detail-value"><?php echo !empty($student['date_of_birth']) ? date('d-m-Y', strtotime($student['date_of_birth'])) : '—'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['phone'] ?? '—'); ?></div>
                </div>
            </div>
            
            <h3 style="margin-top:30px; margin-bottom:20px; color:var(--primary-dark);">Parent/Guardian Information</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Parent Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['parent_name'] ?? '—'); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Parent Phone</div>
                    <div class="detail-value"><?php echo htmlspecialchars($student['parent_phone'] ?? '—'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Tab: Services -->
        <div id="services" class="tab-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; color:var(--primary-dark);">Assigned Services</h3>
            </div>
            
            <?php if (!empty($assignedServices)): ?>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($services as $srv): ?>
                        <?php if (array_key_exists($srv['service_id'], $assignedServices)): ?>
                        <div style="padding:15px; border:1px solid var(--border); border-radius:var(--radius); display:flex; justify-content:space-between; align-items:center; background:var(--light);">
                            <div>
                                <strong style="color:var(--dark);"><?php echo htmlspecialchars($srv['service_name']); ?></strong>
                                <div style="font-size:12px; color:var(--gray); margin-top:4px;"><?php echo htmlspecialchars($srv['description'] ?? ''); ?></div>
                            </div>
                            <div style="font-weight:600; color:var(--primary); display:flex; align-items:center; gap:15px;">
                                <?php echo formatMoney($srv['fee_amount'] ?? 0); ?>
                                <?php $scheduledDate = $assignedServices[$srv['service_id']] ?? null; ?>
                                <?php if ($scheduledDate): ?>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span style="font-size:12px; padding:4px 8px; background:var(--warning); color:#fff; border-radius:4px;"><i class="fas fa-clock"></i> Ends <?php echo date('M d, Y', strtotime($scheduledDate)); ?></span>
                                        <div style="position: relative;" onmouseleave="this.querySelector('.dropdown-menu').style.display='none'">
                                            <button type="button" class="btn btn-sm" style="background:transparent; color:var(--gray); border:none; font-size:16px; padding:4px 8px; cursor:pointer;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu" style="display:none; position:absolute; right:0; top:100%; background:#fff; border:1px solid var(--border); border-radius:var(--radius); box-shadow:0 2px 10px rgba(0,0,0,0.1); z-index:10; min-width:150px; overflow:hidden;">
                                                <a href="#" style="display:block; padding:10px 15px; color:var(--dark); text-decoration:none; font-size:13px; text-align:left;" onclick="event.preventDefault(); this.parentElement.style.display='none'; openDeactivateModal(<?php echo $srv['service_id']; ?>, '<?php echo htmlspecialchars(addslashes($srv['service_name'])); ?>', '<?php echo $scheduledDate; ?>');">
                                                    <i class="fas fa-edit" style="margin-right:8px; color:var(--warning);"></i> Edit Schedule
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-danger" style="padding: 4px 8px; font-size: 12px; cursor:pointer;" onclick="openDeactivateModal(<?php echo $srv['service_id']; ?>, '<?php echo htmlspecialchars(addslashes($srv['service_name'])); ?>', '')"><i class="fas fa-times"></i> Deactivate</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:40px 0;">
                    <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                    <p>No services assigned to this student.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Actions -->
        <div id="actions" class="tab-content">
            <h3 style="margin-top:0; margin-bottom:20px; color:var(--primary-dark);">Manage Student</h3>
            <div class="action-grid">
                <!-- Edit -->
                <div class="action-card" style="border-left: 4px solid var(--primary);">
                    <h3 style="color:var(--primary);"><i class="fas fa-edit"></i> Edit Profile</h3>
                    <p>Update personal info, contact details, and parent information.</p>
                    <button class="btn btn-sm btn-primary" onclick="openEditModal()">Update Info</button>
                </div>
                
                <?php $isInactive = !empty($student['leaving_date']); ?>
                <!-- Assign Services -->
                <div class="action-card" style="border-left: 4px solid var(--success);">
                    <h3 style="color:var(--success);"><i class="fas fa-hand-holding-usd"></i> Manage Services</h3>
                    <p>Enroll the student in optional services like bus transport or hostel.</p>
                    <?php if ($isInactive): ?>
                        <button class="btn btn-sm" style="background:var(--gray); cursor:not-allowed;" disabled>Student Inactive</button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-success" onclick="openModal('servicesModal')">Assign Services</button>
                    <?php endif; ?>
                </div>
                
                <!-- Withdraw -->
                <div class="action-card" style="border-left: 4px solid var(--warning);">
                    <h3 style="color:var(--warning);"><i class="fas fa-sign-out-alt"></i> TC / Withdraw</h3>
                    <p>Issue a transfer certificate or mark the student as withdrawn.</p>
                    <?php if ($isInactive): ?>
                        <button class="btn btn-sm" style="background:var(--gray); cursor:not-allowed;" disabled>Already Withdrawn</button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-warning" onclick="openModal('withdrawModal')">Process Withdrawal</button>
                    <?php endif; ?>
                </div>
                
                <!-- Delete -->
                <div class="action-card" style="border-left: 4px solid var(--danger);">
                    <h3 style="color:var(--danger);"><i class="fas fa-trash"></i> Delete Student</h3>
                    <p>Permanently remove this student. This action cannot be undone.</p>
                    <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" onsubmit="return promptStudentDelete(event, '<?php echo htmlspecialchars(!empty($student['roll_number']) ? $student['roll_number'] : $student['username']); ?>')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals specific to this page -->

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
            <input type="hidden" name="origin" value="details">
            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required pattern="[a-zA-Z\s]+" maxlength="50" value="<?php echo htmlspecialchars($student['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required pattern="[a-zA-Z\s]+" maxlength="50" value="<?php echo htmlspecialchars($student['last_name']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($student['date_of_birth'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Class <i class="fas fa-lock" style="font-size:12px; color:var(--gray); margin-left:4px;"></i></label>
                        <input type="text" value="<?php echo htmlspecialchars($student['class_name'] . ' ' . ($student['section'] ?? '')); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed; color: var(--gray);">
                        <input type="hidden" name="class_id" value="<?php echo $student['current_class_id']; ?>">
                        <small style="color:var(--gray); font-size:11px; display:block; margin-top:4px;">Can't change class manually.</small>
                    </div>
                    <div class="form-group">
                        <label>Roll Number <i class="fas fa-lock" style="font-size:12px; color:var(--gray); margin-left:4px;"></i></label>
                        <input type="text" name="roll_number" value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>" readonly style="background-color: #f1f5f9; cursor: not-allowed; color: var(--gray);">
                        <small style="color:var(--gray); font-size:11px; display:block; margin-top:4px;">Roll Number is system generated/locked.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required pattern="[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$" title="Please enter a valid email address (e.g., user@example.com)" maxlength="150" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent Name</label>
                        <input type="text" name="parent_name" pattern="[a-zA-Z\s]+" title="Parent name should only contain letters and spaces" maxlength="100" value="<?php echo htmlspecialchars($student['parent_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Parent Phone</label>
                        <input type="text" name="parent_phone" pattern="[0-9]{10}" maxlength="10" value="<?php echo htmlspecialchars($student['parent_phone'] ?? ''); ?>">
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
            <input type="hidden" name="origin" value="details">
            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
            <div class="modal-body">
                <?php 
                $unassignedServices = array_filter($services ?? [], function($s) use ($assignedServices) {
                    return !array_key_exists($s['service_id'], $assignedServices);
                });
                ?>
                <?php if (!empty($unassignedServices)): ?>
                    <p style="margin-bottom:15px; color:var(--gray);">Select the services this student will be enrolled in. Note: Newly assigned services will automatically generate an invoice.</p>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <?php foreach ($unassignedServices as $srv): ?>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px; border:1px solid var(--border); border-radius:var(--radius);">
                            <input type="checkbox" name="service_ids[]" value="<?php echo $srv['service_id']; ?>">
                            <span><?php echo htmlspecialchars($srv['service_name']); ?> <br><small style="color:var(--gray);"><?php echo formatMoney($srv['fee_amount'] ?? 0); ?></small></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($services)): ?>
                    <p style="color:var(--gray); padding: 10px 0;">All available services are already assigned.</p>
                <?php else: ?>
                    <p style="color:var(--danger); padding: 10px 0;">No active services found. Add services in the Fee module first.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('servicesModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Assignments</button>
            </div>
        </form>
    </div>
</div>

<!-- Deactivate Service Modal -->
<div id="deactivateModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Deactivate Service</h2>
            <span class="close" onclick="closeModal('deactivateModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="deactivate_service">
            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
            <input type="hidden" name="service_id" id="deactivate_service_id" value="">
            <div class="modal-body">
                <p style="margin-bottom:15px; color:var(--danger);">You are about to <span id="deactivate_modal_action_text">deactivate</span> <strong id="deactivate_service_name"></strong>.</p>
                <div class="form-group">
                    <label>Deactivation Date *</label>
                    <input type="date" name="end_date" id="deactivate_end_date" required min="<?php echo date('Y-m-d'); ?>" value="">
                    <small style="color:var(--gray); display:block; margin-top:5px;">The service remains active until the selected date.</small>
                </div>
            </div>
            <div class="modal-footer" style="display:flex; gap:10px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeModal('deactivateModal')">Close</button>
                <button type="submit" name="cancel_deactivation" value="1" id="cancel_deactivation_btn" class="btn btn-warning" style="display:none; flex:1;"><i class="fas fa-undo"></i> Cancel Schedule</button>
                <button type="submit" class="btn btn-danger" id="deactivate_submit_btn" style="flex:1;"><i class="fas fa-calendar-times"></i> Schedule Deactivation</button>
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
        <form method="POST" action="<?php echo moduleUrl('admin', 'students'); ?>" onsubmit="return confirmWithdrawalWithDues(event, <?php echo $pendingDues > 0 ? 'true' : 'false'; ?>, '<?php echo addslashes(formatMoney($pendingDues)); ?>')">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="withdraw">
            <input type="hidden" name="origin" value="details">
            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
            <div class="modal-body">
                <?php if ($pendingDues > 0): ?>
                <div style="background: var(--danger); color: white; padding: 10px 15px; border-radius: var(--radius); margin-bottom: 15px; font-size: 14px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> This student has unpaid dues of <strong><?php echo formatMoney($pendingDues); ?></strong>.
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Leaving Date *</label>
                    <input type="date" name="leaving_date" value="<?php echo date('Y-m-d'); ?>" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Reason *</label>
                    <select name="leaving_reason" required>
                        <option value="">Select Reason</option>
                        <option value="Passed Out">Passed Out</option>
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
    localStorage.setItem('activeStudentTab', tabName);
}

// Set flag when any form on this page is submitted
document.addEventListener('submit', function() {
    sessionStorage.setItem('detailsFormSubmitted', 'true');
});

// Restore active tab ONLY if returning from a form submission
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeStudentTab');
    const wasSubmitted = sessionStorage.getItem('detailsFormSubmitted');
    
    if (activeTab && wasSubmitted === 'true') {
        const tabBtn = document.querySelector(`.tab-btn[onclick*="'${activeTab}'"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    }
    
    // Clear the flag so a fresh visit (like clicking "View Profile" again) defaults to Overview
    sessionStorage.removeItem('detailsFormSubmitted');
});

function openEditModal() {
    openModal('editModal');
}

function promptStudentDelete(event, expectedValue) {
    const userInput = prompt(`WARNING: You are about to permanently delete this student.\n\nTo confirm, please type the following exactly: ${expectedValue}`);
    
    if (userInput === null) {
        // User clicked Cancel
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
    }
    
    // Sanitize input (trim whitespace and ignore case)
    const sanitizedInput = userInput.trim().toLowerCase();
    const sanitizedExpected = expectedValue.trim().toLowerCase();
    
    if (sanitizedInput !== sanitizedExpected) {
        alert("Confirmation text did not match. Deletion cancelled.");
        event.preventDefault();
        event.stopImmediatePropagation();
        return false;
    }
    
    return true;
}

function confirmWithdrawalWithDues(event, hasDues, amountString) {
    if (hasDues) {
        const proceed = confirm(`WARNING: This student has unpaid dues of ${amountString}.\n\nAre you sure you want to proceed with withdrawing them anyway?`);
        if (!proceed) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return false;
        }
    }
    return true;
}

function openDeactivateModal(serviceId, serviceName, existingDate = '') {
    document.getElementById('deactivate_service_id').value = serviceId;
    document.getElementById('deactivate_service_name').textContent = serviceName;
    
    let dateInput = document.getElementById('deactivate_end_date');
    let cancelBtn = document.getElementById('cancel_deactivation_btn');
    let submitBtn = document.getElementById('deactivate_submit_btn');
    let actionText = document.getElementById('deactivate_modal_action_text');
    
    if (existingDate) {
        dateInput.value = existingDate;
        cancelBtn.style.display = 'inline-block';
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Schedule';
        actionText.textContent = 'update the schedule for';
    } else {
        dateInput.value = '';
        cancelBtn.style.display = 'none';
        submitBtn.innerHTML = '<i class="fas fa-calendar-times"></i> Schedule Deactivation';
        actionText.textContent = 'deactivate';
    }
    
    openModal('deactivateModal');
}
</script>

