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
                    <th class="actions-cell">Actions</th>
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
                                $classList = array_map(function($c) { 
                                    $marksIcon = !empty($c['marks_entered']) ? ' <i class="fas fa-check-circle" style="color:var(--success);" title="Marks Entered"></i>' : '';
                                    return htmlspecialchars($c['class_name'].' '.$c['section']) . $marksIcon; 
                                }, $e['classes']);
                                echo !empty($classList) ? implode(', ', $classList) : 'None';
                            ?>
                        </small>
                    </td>
                    <td><?php echo htmlspecialchars($e['exam_type']); ?></td>
                    <td>
                        <i class="far fa-calendar-alt"></i> <?php echo formatDate($e['start_date']); ?> to <br>
                        <i class="far fa-calendar-check"></i> <?php echo formatDate($e['end_date']); ?>
                    </td>
                    <td>
                        <div style="display:flex; flex-direction:column; align-items:flex-start;">
                            <?php if ($e['is_approved']): ?>
                                <span style="background:#10b981; color:white; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;"><i class="fas fa-check-circle"></i> Approved</span>
                                
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
                                <span style="background:#f59e0b; color:white; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="actions-cell" style="overflow: visible; white-space: nowrap;">
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
            <form method="POST" action="<?php echo moduleUrl('admin', 'examinations'); ?>">
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
                        <label style="display:block; margin-bottom:8px; cursor:pointer; font-weight:bold; color:var(--primary);">
                            <input type="checkbox" id="selectAllClassesAdmin" onclick="toggleAllClasses(this, 'adminClassList')"> Select All Classes
                        </label>
                        <div id="adminClassList" style="max-height: 150px; overflow-y: auto; border: 1px solid var(--border); padding: 10px; border-radius: 4px;">
                            <?php foreach ($classes as $c): ?>
                                <label style="display:block; margin-bottom:5px; cursor:pointer;">
                                    <input type="checkbox" name="class_ids[]" value="<?php echo $c['class_id']; ?>"> 
                                    <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addExamModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create</button>
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
                <div class="table-container">
                    <table class="data-table">
                        <thead>
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
    // Generate the public link
    const currentUrl = window.location.href;
    const baseUrl = currentUrl.split('?')[0];
    const publicLink = baseUrl + '/public/check-result';
    
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
