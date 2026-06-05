<?php
/**
 * Teacher Examinations View — List of exams + Create Class Test modal
 * Variables: $teacher, $exams, $session, $teacherClasses, $teacherSubjects
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-signature"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Examinations for your classes in <?php echo htmlspecialchars($session['session_name'] ?? 'N/A'); ?></p>
    </div>
    <div style="display:flex; gap:10px;">
        <button class="btn btn-primary" onclick="openModal('addExamModal')"><i class="fas fa-plus"></i> Create Class Test</button>
    </div>
</div>

<!-- Add Exam Modal for Teacher -->
<div id="addExamModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2>Create New Class Test</h2>
            <span class="close" onclick="closeModal('addExamModal')">&times;</span>
        </div>
        <form method="POST" action="<?php echo moduleUrl('teacher', 'examinations'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="create_exam">
            <div class="modal-body">
                <div class="form-group">
                    <label>Test Name *</label>
                    <input type="text" name="exam_name" required maxlength="150" placeholder="e.g. Unit 1 Math Test">
                </div>
                <div class="form-group">
                    <label>Select Classes *</label>
                    <label style="display:block; margin-bottom:8px; cursor:pointer; font-weight:bold; color:var(--primary);">
                        <input type="checkbox" id="selectAllClassesTeacher" onclick="toggleAllClasses(this, 'teacherClassList')"> Select All Classes
                    </label>
                    <div id="teacherClassList" style="max-height: 150px; overflow-y: auto; border: 1px solid var(--border); padding: 10px; border-radius: 4px;">
                        <?php if (!empty($teacherClasses)): ?>
                            <?php foreach ($teacherClasses as $c): ?>
                                <label class="teacher-class-item" data-class-id="<?php echo $c['class_id']; ?>" style="display:block; margin-bottom:5px; cursor:pointer;">
                                    <input type="checkbox" name="class_ids[]" value="<?php echo $c['class_id']; ?>" onchange="filterTeacherSubjects()"> 
                                    <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:var(--gray); margin:0;">No classes assigned to you.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Subject * <small style="color:var(--gray);">(Class Tests are for one subject only)</small></label>
                    <select name="subject_id" id="testSubjectSelect" required>
                        <option value="">-- Select Classes First --</option>
                        <?php if (!empty($teacherSubjects)): ?>
                            <?php foreach ($teacherSubjects as $s): ?>
                                <option value="<?php echo $s['subject_id']; ?>" style="display:none;" disabled>
                                    <?php echo htmlspecialchars($s['subject_name'] . ' (' . $s['subject_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="row" style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" id="teacherExamStartDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>End Date *</label>
                        <input type="date" name="end_date" id="teacherExamEndDate" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="row" style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Full Marks *</label>
                        <input type="number" name="full_marks" required value="50" min="1" max="200">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Pass Marks *</label>
                        <input type="number" name="pass_marks" required value="17" min="0" max="200">
                    </div>
                </div>

                <div class="alert alert-info" style="margin-top:15px; padding:10px;">
                    <i class="fas fa-info-circle"></i> Class Tests require Administrator approval before results can be finalized.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addExamModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Submit Request</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($exams)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-file-circle-xmark"></i></div>
        <p>No examinations found for your classes this session.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Classes</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong></td>
                        <td><span class="badge" style="background: var(--primary-light, #e3f2fd); color: white; padding: 3px 8px; border-radius: 4px;"><?php echo htmlspecialchars($exam['exam_type']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($exam['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($exam['end_date'])); ?></td>
                        <td>
                            <?php foreach ($exam['classes'] as $c): ?>
                                <span class="badge" style="background: <?php echo !empty($c['marks_entered']) ? '#d4edda' : '#e0e0e0'; ?>; color: <?php echo !empty($c['marks_entered']) ? '#155724' : 'inherit'; ?>; padding: 3px 8px; border-radius: 4px; margin: 2px; display:inline-block;">
                                    <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                                    <?php if (!empty($c['marks_entered'])): ?> <i class="fas fa-check-circle" title="Marks Entered"></i><?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php 
                            $now = date('Y-m-d');
                            if (!$exam['is_approved']) {
                                echo '<span style="color: #FF5722; font-weight:bold;"><i class="fas fa-clock"></i> Pending Approval</span>';
                            } else {
                                if ($now < $exam['start_date']) echo '<span style="color: #FF9800;">Upcoming</span>';
                                elseif ($now > $exam['end_date']) echo '<span style="color: #4CAF50;">Completed</span>';
                                else echo '<span style="color: #2196F3;">In Progress</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $examEnded = date('Y-m-d') >= $exam['end_date'];
                            $isApproved = $exam['is_approved'];
                            ?>
                            <?php if ($isApproved && $examEnded): ?>
                                <?php foreach ($exam['classes'] as $c): ?>
                                    <?php if (!empty($c['has_results'])): ?>
                                        <button class="btn btn-sm btn-secondary" style="margin: 2px; font-size: 0.8rem; cursor: not-allowed; opacity: 0.8;" disabled>
                                            <i class="fas fa-lock"></i> Results Locked (<?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>)
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo moduleUrl('teacher', 'results'); ?>?exam_id=<?php echo $exam['exam_id']; ?>&class_id=<?php echo $c['class_id']; ?>" 
                                           class="btn btn-sm btn-primary" style="margin: 2px; font-size: 0.8rem;">
                                            Enter Results (<?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>)
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php elseif (!$isApproved): ?>
                                <span style="color: var(--gray); font-style: italic;">Awaiting approval</span>
                            <?php else: ?>
                                <span style="color: var(--gray); font-style: italic;">Exam not ended yet</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
const subjectClassMap = <?php echo json_encode($subjectClassMap ?? []); ?>;

function filterTeacherSubjects() {
    const classCheckboxes = document.querySelectorAll('input[name="class_ids[]"]:checked');
    const subjectSelect = document.getElementById('testSubjectSelect');
    
    if (classCheckboxes.length === 0) {
        // If no classes selected, hide and disable all subjects
        Array.from(subjectSelect.options).forEach(opt => {
            if (opt.value !== "") {
                opt.style.display = 'none';
                opt.disabled = true;
            } else {
                opt.text = "-- Select Classes First --";
            }
        });
        subjectSelect.value = "";
        return;
    }
    
    // Check if a subject is taught in ALL selected classes
    const selectedClassIds = Array.from(classCheckboxes).map(cb => String(cb.value));
    
    let hasValidSubject = false;
    Array.from(subjectSelect.options).forEach(opt => {
        if (opt.value === "") {
            opt.text = "-- Select Subject --";
            return;
        }
        
        const subjectId = opt.value;
        const allowedClasses = (subjectClassMap[subjectId] || []).map(String);
        
        const isValid = selectedClassIds.every(cid => allowedClasses.includes(cid));
        
        if (isValid) {
            opt.style.display = 'block';
            opt.disabled = false;
            hasValidSubject = true;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
            if (subjectSelect.value === subjectId) {
                subjectSelect.value = ""; // Deselect if invalidated
            }
        }
    });
    
    if (!hasValidSubject) {
        subjectSelect.options[0].text = "-- No common subjects for selected classes --";
    }
}

function toggleAllClasses(masterCheckbox, containerId) {
    var container = document.getElementById(containerId);
    var checkboxes = container.querySelectorAll('input[name="class_ids[]"]');
    checkboxes.forEach(function(cb) {
        cb.checked = masterCheckbox.checked;
    });
    filterTeacherSubjects();
}

// Sync start date with end date min
document.addEventListener('DOMContentLoaded', function() {
    filterTeacherSubjects(); // Run once on load to set initial state
    
    var startDate = document.getElementById('teacherExamStartDate');
    var endDate = document.getElementById('teacherExamEndDate');
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
