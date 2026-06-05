<?php
/**
/**
 * Exams View (Admin)
 * Variables: $exams, $classes, $session, $pageTitle
 */
?>
<style>
.action-menu { position: relative; display: inline-flex; align-items: center; gap: 5px; }
.action-menu-btn { background: none; border: none; cursor: pointer; font-size: 18px; color: var(--gray); padding: 5px 10px; border-radius: 4px; }
.action-menu-btn:hover { background: #f1f5f9; color: var(--primary); }
.action-menu-content { display: none; position: absolute; right: 0; top: 100%; background-color: white; min-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; border-radius: 6px; overflow: hidden; border: 1px solid var(--border); }
.action-menu-content a, .action-menu-content button { display: block; width: 100%; text-align: left; padding: 10px 15px; text-decoration: none; color: var(--text-color); border: none; background: none; cursor: pointer; font-size: 13px; font-family: inherit; }
.action-menu-content a:hover, .action-menu-content button:hover { background-color: #f8fafc; color: var(--primary); }
.action-menu-content .text-danger:hover { color: #ef4444; }
.action-menu:hover .action-menu-content { display: block; }
tbody tr:last-child .action-menu-content, 
tbody tr:nth-last-child(2) .action-menu-content { top: auto; bottom: 100%; }
</style>
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-signature"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Manage examinations and assigned classes</p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-warning" onclick="openModal('bulkExamModal')" style="box-shadow: 0 2px 4px rgba(255,152,0,0.3);"><i class="fas fa-bolt"></i> Generate Exam Fees</button>
        <button class="btn btn-primary" onclick="openModal('addExamModal')"><i class="fas fa-plus"></i> Create Exam</button>
    </div>
</div>

<?php if (!$session): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> No active academic session found.</div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($exams)): ?>
                <?php foreach ($exams as $e): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($e['exam_name']); ?></strong><br>
                        <small style="color:var(--gray);">
                            Classes: 
                            <?php 
                                $classList = array_map(function($c) use ($e) { 
                                    $classNameStr = $c['class_name'].' '.$c['section'];
                                    
                                    $isMissing = false;
                                    if (isset($e['missing_reports'])) {
                                        foreach ($e['missing_reports'] as $mr) {
                                            if ($mr['class_name'] === $classNameStr) {
                                                $isMissing = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    $hasMarks = !empty($c['marks_entered']);
                                    $marksIcon = '';
                                    
                                    if ($hasMarks && !$isMissing) {
                                        $marksIcon = ' <i class="fas fa-check-circle" style="color:#10b981;" title="Marks Entry Complete"></i>';
                                    } elseif ($hasMarks && $isMissing) {
                                        $marksIcon = ' <i class="fas fa-pen" style="color:#f59e0b;" title="Marks Entry In Progress"></i>';
                                    }
                                    
                                    return htmlspecialchars($classNameStr) . $marksIcon; 
                                }, $e['classes']);
                                echo !empty($classList) ? implode(', ', $classList) : 'None';
                            ?>
                        </small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($e['exam_type']); ?>
                        <div style="margin-top: 4px;">
                            <small style="color:var(--gray); font-size: 11px;"><i class="fas fa-user-edit"></i> Created by: <?php echo htmlspecialchars($e['creator_name'] ?? 'Unknown'); ?></small>
                        </div>
                    </td>
                    <td>
                        <span style="white-space: nowrap;"><i class="far fa-calendar-alt" style="color:var(--gray);"></i> <?php echo formatDate($e['start_date']); ?> &nbsp;&ndash;&nbsp; <?php echo formatDate($e['end_date']); ?></span>
                    </td>
                    <td>
                        <div style="display:flex; flex-direction:column; align-items:flex-start;">
                            <?php if ($e['is_approved']): ?>
                                <span style="background:#10b981; color:white; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600;"><i class="fas fa-check-circle"></i> Approved</span>
                                
                                <div style="margin-top: 8px; font-size: 11px; color: #475569; display: flex; flex-direction: column; gap: 4px; background: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <div style="display:flex; align-items:center; gap:5px;">
                                        <i class="fas fa-calendar-alt" style="color:#94a3b8; width:12px;"></i> 
                                        <span>Schedule:</span>
                                        <?php if (!empty($e['is_schedule_published'])): ?>
                                            <span style="color:#10b981; font-weight:700;">Public</span>
                                        <?php else: ?>
                                            <span style="color:#ef4444; font-weight:700;">Private</span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display:flex; align-items:center; gap:5px;">
                                        <i class="fas fa-tasks" style="color:#94a3b8; width:12px;"></i> 
                                        <span>Results:</span>
                                        <?php if (!empty($e['is_published'])): ?>
                                            <span style="color:#3b82f6; font-weight:700;">Public</span>
                                        <?php else: ?>
                                            <span style="color:#ef4444; font-weight:700;">Private</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php else: ?>
                                <span style="background:#f59e0b; color:white; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:600;"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="overflow: visible; white-space: nowrap;">
                        <div style="display: inline-flex; align-items: center; gap: 5px;">
                            <?php if (!$e['is_approved']): ?>
                                <form method="POST" action="<?php echo moduleUrl('admin', 'examinations'); ?>" style="margin:0;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="approve_exam">
                                    <input type="hidden" name="exam_id" value="<?php echo $e['exam_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success" style="border-radius: 4px;"><i class="fas fa-check"></i> Approve</button>
                                </form>
                            <?php elseif (!empty($e['classes'])): ?>
                                <a href="<?php echo moduleUrl('admin', 'schedules'); ?>?exam_id=<?php echo $e['exam_id']; ?>&class_id=<?php echo $e['classes'][0]['class_id']; ?>" class="btn btn-sm btn-info" style="border-radius: 4px;"><i class="fas fa-calendar-alt"></i> Manage Schedule</a>
                            <?php endif; ?>

                            <div class="action-menu">
                                <button class="action-menu-btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="action-menu-content">
                                    <button type="button" onclick='openEditExamModal(<?php echo json_encode([
                                        "exam_id" => $e["exam_id"],
                                        "exam_type" => $e["exam_type"],
                                        "start_date" => $e["start_date"],
                                        "end_date" => $e["end_date"],
                                        "class_ids" => array_column($e["classes"], "class_id")
                                    ]); ?>)'>
                                        <i class="fas fa-edit" style="width:20px; color:var(--info);"></i> Edit Exam
                                    </button>
                                    <?php if (!empty($e['classes'])): ?>
                                        <button type="button" onclick="window.location.href='<?php echo moduleUrl('admin', 'master_schedule'); ?>?exam_id=<?php echo $e['exam_id']; ?>'">
                                            <i class="fas fa-table" style="width:20px; color:var(--primary);"></i> Master Routine
                                        </button>
                                    <?php endif; ?>
                                    <hr style="margin: 5px 0; border: none; border-top: 1px solid var(--border);">
                                    <?php if ($e['is_approved']): ?>
                                        <form method="POST" action="<?php echo moduleUrl('admin', 'examinations'); ?>" style="margin:0;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_publish_schedule">
                                            <input type="hidden" name="exam_id" value="<?php echo $e['exam_id']; ?>">
                                            <input type="hidden" name="state" value="<?php echo isset($e['is_schedule_published']) && $e['is_schedule_published'] ? '0' : '1'; ?>">
                                            <button type="submit">
                                                <i class="fas <?php echo isset($e['is_schedule_published']) && $e['is_schedule_published'] ? 'fa-eye-slash' : 'fa-eye'; ?>" style="width:20px; color: <?php echo isset($e['is_schedule_published']) && $e['is_schedule_published'] ? '#ef4444' : '#10b981'; ?>;"></i> 
                                                <?php echo isset($e['is_schedule_published']) && $e['is_schedule_published'] ? 'Unpublish Schedule' : 'Publish Schedule'; ?>
                                            </button>
                                        </form>

                                        <!-- View Marks Progress -->
                                        <button type="button" onclick="viewMarksProgress(<?php echo $e['exam_id']; ?>)">
                                            <i class="fas fa-tasks" style="width:20px; color:var(--info);"></i> Marks Progress
                                        </button>

                                        <form method="POST" action="<?php echo moduleUrl('admin', 'examinations'); ?>" style="margin:0;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_publish_results">
                                            <input type="hidden" name="exam_id" value="<?php echo $e['exam_id']; ?>">
                                            <input type="hidden" name="state" value="<?php echo isset($e['is_published']) && $e['is_published'] ? '0' : '1'; ?>">
                                            <?php if (isset($e['is_published']) && !$e['is_published'] && isset($e['is_marks_complete']) && !$e['is_marks_complete']): ?>
                                                <button type="button" onclick="alert('Cannot publish results until all marks are entered. Please check Marks Progress.')" style="color:var(--gray);">
                                                    <i class="fas fa-lock" style="width:20px;"></i> Publish Results
                                                </button>
                                            <?php else: ?>
                                                <button type="submit">
                                                    <i class="fas <?php echo isset($e['is_published']) && $e['is_published'] ? 'fa-eye-slash' : 'fa-bullhorn'; ?>" style="width:20px; color: <?php echo isset($e['is_published']) && $e['is_published'] ? '#f59e0b' : '#3b82f6'; ?>;"></i> 
                                                    <?php echo isset($e['is_published']) && $e['is_published'] ? 'Unpublish Results' : 'Publish Results'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <?php if (isset($e['is_published']) && $e['is_published']): ?>
                                            <hr style="margin: 5px 0; border: none; border-top: 1px solid var(--border);">
                                            <button type="button" onclick="copyPublicLink()">
                                                <i class="fas fa-link" style="width:20px;"></i> Share Result Link
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5"><div class="empty-state"><p>No exams created yet for this session.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Exam Modal -->
    <div id="addExamModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Create New Exam</h2>
                <span class="close" onclick="closeModal('addExamModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo moduleUrl('admin', 'examinations'); ?>" onsubmit="return validateAddExamForm(this)">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create_exam">
                <div class="modal-body">
                    <div class="row" style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Exam Type *</label>
                            <select name="exam_type" id="adminExamType" required>
                                <option value="Unit Test">Unit Test</option>
                                <option value="Mid-Term">Mid-Term</option>
                                <option value="Final">Final</option>
                                <option value="Class Test">Class Test</option>
                            </select>
                        </div>
                    </div>
                    <div class="row" style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" id="adminExamStartDate" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>End Date *</label>
                            <input type="date" name="end_date" id="adminExamEndDate" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Assign to Classes *</label>
                        <label style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 8px; cursor: pointer; font-weight: bold; color: var(--primary); text-align: left;">
                            <input type="checkbox" id="selectAllClassesAdmin" onclick="toggleAllClasses(this, 'adminClassList')" style="margin: 0;"> <span>Select All Classes</span>
                        </label>
                        <div id="adminClassList" style="max-height: 150px; overflow-y: auto; border: 1px solid var(--border); padding: 10px; border-radius: 4px; text-align: left;">
                            <?php foreach ($classes as $c): ?>
                                <label style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 5px; cursor: pointer; text-align: left;">
                                    <input type="checkbox" name="class_ids[]" value="<?php echo $c['class_id']; ?>" style="margin: 0;"> 
                                    <span><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addExamModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create</button>
                </div>
                <script>
                    function validateAddExamForm(form) {
                        var start = new Date(form.querySelector('#adminExamStartDate').value);
                        var end = new Date(form.querySelector('#adminExamEndDate').value);
                        if (start > end) {
                            alert('End Date cannot be before Start Date.');
                            return false;
                        }
                        var checkboxes = form.querySelectorAll('input[name="class_ids[]"]:checked');
                        if (checkboxes.length === 0) {
                            alert('Please assign this exam to at least one class.');
                            return false;
                        }
                        return true;
                    }
                </script>
            </form>
        </div>
    </div>
    <!-- Bulk Generate Exam Fees Modal -->
    <div id="bulkExamModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Generate Exam Fees (Bulk)</h2>
                <span class="close" onclick="closeModal('bulkExamModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo moduleUrl('admin', 'fee_collection'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="generate_exam_fees">
                <input type="hidden" name="redirect_to" value="examinations">
                
                <div class="modal-body">
                    <p style="margin-bottom:15px; color:var(--text-light);">This will generate a pending exam fee invoice for all active students in the selected class who haven't been billed yet.</p>
                    <div class="form-group">
                        <label>Select Class *</label>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section'] . ' (Exam Fee: ₹' . $c['exam_fee'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Exam *</label>
                        <select name="exam_id" required>
                            <option value="">-- Select Exam --</option>
                            <?php if(!empty($exams)): ?>
                                <?php foreach ($exams as $e): ?>
                                    <option value="<?php echo $e['exam_id']; ?>"><?php echo htmlspecialchars($e['exam_name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning" style="width:100%;"><i class="fas fa-bolt"></i> Generate Fees</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Marks Progress Modal -->
<div id="progressModal" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2>Marks Entry Progress</h2>
            <span class="close" onclick="closeModal('progressModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div id="progressLoading" style="text-align:center; padding: 20px;">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p style="margin-top:10px;">Loading progress report...</p>
            </div>
            <div id="progressData" style="display:none;">
                <p id="progressStatus" style="font-weight:bold; margin-bottom: 15px;"></p>
                <div class="table-container" style="max-height: 400px; overflow-y: auto;">
                    <table class="data-table">
                        <thead style="position: sticky; top: 0; z-index: 2; background: #f8f9fa;">
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody id="progressTableBody">
                            <!-- Populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('progressModal')">Close</button>
        </div>
    </div>
</div>

<script>
function toggleAllClasses(masterCheckbox, containerId) {
    var container = document.getElementById(containerId);
    var checkboxes = container.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        cb.checked = masterCheckbox.checked;
    });
}

// Sync start date with end date min
document.addEventListener('DOMContentLoaded', function() {
    var startDate = document.getElementById('adminExamStartDate');
    var endDate = document.getElementById('adminExamEndDate');
    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            endDate.min = this.value;
            if (endDate.value && endDate.value < this.value) {
                endDate.value = this.value;
            }
        });
    }
});
</script>
<script>
function copyPublicLink() {
    // Generate the public link using BASE_URL
    const publicLink = '<?php echo BASE_URL; ?>/public/check-result';
    
    // Copy to clipboard
    navigator.clipboard.writeText(publicLink).then(() => {
        alert("Public Results Link copied to clipboard:\n" + publicLink);
    }).catch(err => {
        alert("Failed to copy link. The link is: " + publicLink);
    });
}

function viewMarksProgress(examId) {
    openModal('progressModal');
    document.getElementById('progressLoading').style.display = 'block';
    document.getElementById('progressData').style.display = 'none';

    fetch('<?php echo BASE_URL; ?>/admin/examinations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'action=get_missing_marks&exam_id=' + examId + '&_csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('progressLoading').style.display = 'none';
        document.getElementById('progressData').style.display = 'block';
        
        let tbody = document.getElementById('progressTableBody');
        tbody.innerHTML = '';
        
        if (data.reports.length === 0) {
            document.getElementById('progressStatus').innerHTML = '<span style="color:var(--success);"><i class="fas fa-check-circle"></i> All marks for this exam have been completely entered.</span>';
        } else {
            document.getElementById('progressStatus').innerHTML = '<span style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Missing marks detected. Results cannot be published.</span>';
            data.reports.forEach(r => {
                let color = r.entered === 0 ? 'var(--danger)' : 'var(--warning)';
                tbody.innerHTML += `
                    <tr>
                        <td>${r.class_name}</td>
                        <td>${r.subject_name}</td>
                        <td>${r.teacher_name}</td>
                        <td>
                            <strong style="color:${color}">${r.entered} / ${r.total}</strong>
                            <div style="background:#eee; height:6px; border-radius:3px; margin-top:5px; overflow:hidden;">
                                <div style="background:${color}; width:${(r.entered/r.total)*100}%; height:100%;"></div>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
    })
    .catch(e => {
        document.getElementById('progressLoading').innerHTML = '<p class="text-danger">Failed to load report.</p>';
    });
}
</script>

    <!-- Edit Exam Modal -->
    <div id="editExamModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Edit Exam</h2>
                <span class="close" onclick="closeModal('editExamModal')">&times;</span>
            </div>
            <form method="POST" action="<?php echo moduleUrl('admin', 'examinations'); ?>" onsubmit="return validateEditExamForm(this)">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_exam">
                <input type="hidden" name="exam_id" id="editExamId">
                <input type="hidden" name="exam_type" id="editExamTypeHidden">
                <input type="hidden" id="originalStartDate">
                <div class="modal-body">
                    <div class="row" style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Exam Type * (Locked)</label>
                            <select id="editExamType" disabled style="background:#f1f5f9; cursor:not-allowed; opacity:0.8;">
                                <option value="Unit Test">Unit Test</option>
                                <option value="Mid-Term">Mid-Term</option>
                                <option value="Final">Final</option>
                                <option value="Class Test">Class Test</option>
                            </select>
                        </div>
                    </div>
                    <div class="row" style="display:flex; gap:15px;">
                        <div class="form-group" style="flex:1;">
                            <label>Start Date *</label>
                            <input type="date" name="start_date" id="editExamStartDate" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>End Date *</label>
                            <input type="date" name="end_date" id="editExamEndDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Assign to Classes *</label>
                        <label style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 8px; cursor: pointer; font-weight: bold; color: var(--primary); text-align: left;">
                            <input type="checkbox" id="selectAllClassesEdit" onclick="toggleAllClasses(this, 'editClassList')" style="margin: 0;"> <span>Select All Classes</span>
                        </label>
                        <div id="editClassList" style="max-height: 150px; overflow-y: auto; border: 1px solid var(--border); padding: 10px; border-radius: 4px; text-align: left;">
                            <?php foreach ($classes as $c): ?>
                                <label style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 5px; cursor: pointer; text-align: left;">
                                    <input type="checkbox" name="class_ids[]" value="<?php echo $c['class_id']; ?>" class="edit-class-checkbox" style="margin: 0;"> 
                                    <span><?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editExamModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </div>
                <script>
                    function validateEditExamForm(form) {
                        var startVal = form.querySelector('#editExamStartDate').value;
                        var endVal = form.querySelector('#editExamEndDate').value;
                        var origStart = form.querySelector('#originalStartDate').value;
                        var today = new Date().toISOString().split('T')[0];
                        
                        // If they changed the start date to a past date
                        if (startVal !== origStart && startVal < today) {
                            alert('Start Date cannot be set to a past date.');
                            return false;
                        }

                        if (startVal > endVal) {
                            alert('End Date cannot be before Start Date.');
                            return false;
                        }
                        var checkboxes = form.querySelectorAll('#editClassList input[name="class_ids[]"]:checked');
                        if (checkboxes.length === 0) {
                            alert('Please assign this exam to at least one class.');
                            return false;
                        }
                        return true;
                    }
                </script>
            </form>
        </div>
    </div>

<script>
function openEditExamModal(examData) {
    document.getElementById('editExamId').value = examData.exam_id;
    document.getElementById('editExamType').value = examData.exam_type;
    document.getElementById('editExamTypeHidden').value = examData.exam_type;
    
    document.getElementById('editExamStartDate').value = examData.start_date;
    document.getElementById('originalStartDate').value = examData.start_date;
    document.getElementById('editExamEndDate').value = examData.end_date;
    
    // Set dynamic minimums so HTML5 validation works for future dates
    let today = new Date().toISOString().split('T')[0];
    if (examData.start_date >= today) {
        document.getElementById('editExamStartDate').setAttribute('min', today);
        document.getElementById('editExamEndDate').setAttribute('min', today);
    } else {
        document.getElementById('editExamStartDate').removeAttribute('min');
        document.getElementById('editExamEndDate').removeAttribute('min');
    }
    
    // Clear all checkboxes first
    document.querySelectorAll('.edit-class-checkbox').forEach(cb => cb.checked = false);
    
    // Check the ones assigned
    if (examData.class_ids && examData.class_ids.length > 0) {
        examData.class_ids.forEach(cid => {
            let cb = document.querySelector('.edit-class-checkbox[value="' + cid + '"]');
            if (cb) cb.checked = true;
        });
    }
    
    openModal('editExamModal');
}
</script>
