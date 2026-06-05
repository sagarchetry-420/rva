<?php
/**
 * Attendance Marking View
 * Variables: $classes, $filterClass, $attendanceDate, $students, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-clipboard-user"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Record daily student attendance</p>
    </div>
    <?php if ($_SESSION['user_type'] === 'admin'): ?>
    <a href="<?php echo moduleUrl('admin', 'index'); ?>?action=attendance/monthly" class="btn btn-info"><i class="fas fa-calendar-alt"></i> View Monthly Report</a>
    <?php endif; ?>
</div>

<div class="filter-bar">
    <form method="GET" style="display:flex; gap:15px; align-items:flex-end; width:100%; flex-wrap:wrap;">
        <input type="hidden" name="module" value="<?php echo htmlspecialchars($_GET['module'] ?? 'admin'); ?>">
        <input type="hidden" name="action" value="<?php echo htmlspecialchars($_GET['action'] ?? 'attendance'); ?>">
        
        <div class="filter-group" style="flex:1;">
            <label>Class</label>
            <select name="class_id" class="form-control" required>
                <option value="">-- Select Class --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo $c['class_id']; ?>" <?php echo $filterClass == $c['class_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['class_name'] . ' ' . $c['section']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group" style="flex:1;">
            <label>Date</label>
            <?php if ($_SESSION['user_type'] === 'teacher'): ?>
                <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
            <?php else: ?>
                <input type="date" name="attendance_date" class="form-control" value="<?php echo htmlspecialchars($attendanceDate); ?>" required max="<?php echo date('Y-m-d'); ?>">
            <?php endif; ?>
        </div>
        
        <div class="filter-group" style="flex:1;">
            <label>Search Student</label>
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>" placeholder="Name or Roll No">
        </div>
        
        <div class="filter-group">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Load Students</button>
        </div>
    </form>
</div>

<?php if ($_SESSION['user_type'] === 'admin' && $filterClass): ?>
<div style="text-align: right; margin-bottom: 15px;">
    <form method="POST" action="<?php echo moduleUrl('admin', 'attendance'); ?>" class="no-auto-validate" style="display:inline;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="exportPdf">
        <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
        <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendanceDate); ?>">
        <button type="submit" class="btn btn-secondary"><i class="fas fa-file-pdf"></i> Download Class PDF</button>
    </form>
</div>
<?php endif; ?>

<?php 
    $alreadyMarked = ($_SESSION['user_type'] === 'teacher' && ($isMarked ?? false));
?>

<?php if ($filterClass && empty($students)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
        <p>No active students found for this class in the current session.</p>
    </div>
<?php elseif ($filterClass && !empty($students)): ?>
    <?php if ($alreadyMarked): ?>
        <div class="alert alert-warning" style="padding: 15px; margin-bottom: 20px; border-radius: 4px; background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Notice:</strong> Attendance for this class has already been marked for this date. You cannot modify it. If corrections are needed, please contact the administrator.
        </div>
    <?php endif; ?>

    <div class="table-container">
        <style>
            @media (max-width: 768px) {
                .attendance-radios { gap: 2px !important; flex-wrap: nowrap !important; }
                .attendance-radios .radio-btn span { padding: 2px 5px !important; font-size: 10px !important; white-space: nowrap !important; }
            }
        </style>
        <?php if ($_SESSION['user_type'] === 'teacher' && !$alreadyMarked): ?>
        <form method="POST" action="<?php echo moduleUrl('teacher', 'attendance'); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendanceDate); ?>">
        <?php endif; ?>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Status</th>
                        <th>Remarks (Optional)</th>
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                        <th>Admin Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <?php 
                        $rawStatus = $s['status'] ?? null;
                        $isLeave = in_array($rawStatus, ['Leave', 'Half Leave']);
                        $showReadOnly = ($_SESSION['user_type'] === 'admin' || $isLeave || $alreadyMarked);
                        $currentStatus = $rawStatus ? $rawStatus : ($showReadOnly ? 'Not Marked' : 'Present');
                        
                        $statusColor = 'var(--success)';
                        if ($isLeave) $statusColor = 'var(--warning)';
                        elseif ($currentStatus === 'Absent') $statusColor = 'var(--danger)';
                        elseif ($currentStatus === 'Not Marked') $statusColor = '#6c757d';

                        $rawRemarks = $s['remarks'] ?? '';
                        $cleanRemarks = $rawRemarks;
                        $dateRangeStr = date('d-m-Y', strtotime($attendanceDate)); // Default to the selected date
                        if ($isLeave && preg_match('/\((From: .*? To: .*?|Date: .*?)\)$/', $rawRemarks, $matches)) {
                            $dateRangeStr = $matches[1];
                            $cleanRemarks = trim(str_replace($matches[0], '', $rawRemarks));
                        }
                        
                        $displayLeaveType = ($currentStatus === 'Leave') ? 'Full Leave' : $currentStatus;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['roll_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                        <td>
                            <?php if ($showReadOnly): ?>
                                <strong style="color: <?php echo $statusColor; ?>; <?php if($isLeave) echo 'cursor:pointer; text-decoration:underline;'; ?>" 
                                    <?php if($isLeave): ?>onclick="viewLeaveDetails('<?php echo htmlspecialchars(addslashes($s['first_name'] . ' ' . $s['last_name'])); ?>', '<?php echo htmlspecialchars(addslashes($displayLeaveType)); ?>', '<?php echo htmlspecialchars(addslashes($dateRangeStr)); ?>', '<?php echo htmlspecialchars(addslashes($cleanRemarks)); ?>')" title="Click to view details"<?php endif; ?>>
                                    <?php echo htmlspecialchars($currentStatus); ?>
                                </strong>
                                <?php if ($isLeave && ! $alreadyMarked): ?>
                                    <input type="hidden" name="attendance[<?php echo $s['student_id']; ?>][status]" value="<?php echo htmlspecialchars($currentStatus); ?>">
                                <?php endif; ?>
                                <?php if (!empty($s['leave_document'])): ?>
                                    <br><a href="<?php echo BASE_URL . '/' . $s['leave_document']; ?>" target="_blank" style="font-size: 12px; color: var(--primary);"><i class="fas fa-paperclip"></i> View Document</a>
                                <?php endif; ?>
                            <?php else: ?>
                            <div class="attendance-radios" style="display:flex; align-items:center;">
                                <label class="radio-btn radio-present">
                                    <input type="radio" name="attendance[<?php echo $s['student_id']; ?>][status]" value="Present" <?php echo $currentStatus === 'Present' ? 'checked' : ''; ?>> 
                                    <span>Present</span>
                                </label>
                                <label class="radio-btn radio-absent">
                                    <input type="radio" name="attendance[<?php echo $s['student_id']; ?>][status]" value="Absent" <?php echo $currentStatus === 'Absent' ? 'checked' : ''; ?>> 
                                    <span>Absent</span>
                                </label>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($_SESSION['user_type'] === 'admin' || $isLeave || $alreadyMarked): ?>
                                <?php echo htmlspecialchars($cleanRemarks); ?>
                                <?php if ($isLeave && ! $alreadyMarked): ?>
                                    <input type="hidden" name="attendance[<?php echo $s['student_id']; ?>][remarks]" value="<?php echo htmlspecialchars($rawRemarks); ?>">
                                <?php endif; ?>
                            <?php else: ?>
                                <input type="text" name="attendance[<?php echo $s['student_id']; ?>][remarks]" value="<?php echo htmlspecialchars($cleanRemarks); ?>" placeholder="Add note..." style="width:100%; padding:5px; border:1px solid var(--border); border-radius:4px;">
                            <?php endif; ?>
                        </td>
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                        <td>
                            <form method="POST" action="<?php echo moduleUrl('admin', 'attendance'); ?>" class="no-auto-validate" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="exportPdf">
                                <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                                <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($attendanceDate); ?>">
                                <input type="hidden" name="student_id" value="<?php echo $s['student_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Download Report"><i class="fas fa-file-pdf"></i></button>
                            </form>
                            <?php if (!$isLeave): ?>
                                <button type="button" class="btn btn-sm btn-warning" onclick="openLeaveModal(<?php echo $s['student_id']; ?>, '<?php echo htmlspecialchars(addslashes($s['first_name'] . ' ' . $s['last_name'])); ?>')"><i class="fas fa-calendar-minus"></i> Apply Leave</button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination" style="margin-top: 15px; display: flex; gap: 5px; justify-content: center;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?module=<?php echo htmlspecialchars($_GET['module'] ?? 'admin'); ?>&action=attendance&class_id=<?php echo $filterClass; ?>&attendance_date=<?php echo htmlspecialchars($attendanceDate); ?>&search=<?php echo urlencode($searchQuery ?? ''); ?>&page=<?php echo $i; ?>" 
                       class="btn btn-sm <?php echo (isset($currentPage) && $currentPage == $i) ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 5px 10px; border-radius: 4px; text-decoration: none;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            
        <?php if ($_SESSION['user_type'] === 'teacher' && !$alreadyMarked): ?>
            <div style="margin-top:20px; text-align:right;">
                <button type="submit" class="btn btn-primary" style="padding:10px 30px; font-size:16px;">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
            </div>
        </form>
        <?php endif; ?>

        <?php if ($_SESSION['user_type'] === 'admin'): ?>
        <!-- Leave Application Modal (Admin Only) -->
        <div id="leaveModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:20px; border-radius:8px; width:400px; max-width:90%;">
                <h3>Apply Leave for <span id="leaveStudentName"></span></h3>
                <form method="POST" action="<?php echo moduleUrl('admin', 'attendance'); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="applyLeave">
                    <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                    <input type="hidden" name="student_id" id="leaveStudentId" value="">
                    
                    <div class="form-group" style="margin-bottom:10px;">
                        <label>Leave Type</label>
                        <select name="leave_status" id="leaveType" class="form-control" required onchange="toggleEndDate(this.value)">
                            <option value="Leave">Full Leave</option>
                            <option value="Half Leave">Half Leave</option>
                        </select>
                    </div>
                    
                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <div class="form-group" id="startDateGroup" style="flex:1;">
                            <label>Start Date</label>
                            <input type="date" id="startDate" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required onchange="document.getElementById('endDate').min = this.value; if(document.getElementById('endDate').value < this.value) document.getElementById('endDate').value = this.value;">
                        </div>
                        <div class="form-group" id="endDateGroup" style="flex:1;">
                            <label>End Date</label>
                            <input type="date" id="endDate" name="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom:10px;">
                        <label>Remarks / Reason</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Medical leave" pattern="^[a-zA-Z0-9\s.,!?'\(\)-]+$" title="Only letters, numbers, spaces, and basic punctuation are allowed.">
                    </div>

                    <div class="form-group" style="margin-bottom:15px;">
                        <label>Application Document (PDF/Photo)</label>
                        <input type="file" name="leave_document" class="form-control" accept="image/jpeg,image/png,application/pdf">
                    </div>
                    
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('leaveModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn btn-warning">Submit Leave</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function openLeaveModal(studentId, studentName) {
                document.getElementById('leaveStudentId').value = studentId;
                document.getElementById('leaveStudentName').textContent = studentName;
                document.getElementById('leaveType').value = 'Leave';
                toggleEndDate('Leave');
                document.getElementById('leaveModal').style.display = 'flex';
            }

            function toggleEndDate(leaveType) {
                const endDateGroup = document.getElementById('endDateGroup');
                const startDateGroup = document.getElementById('startDateGroup');
                const endDateInput = document.getElementById('endDate');
                const startDateInput = document.getElementById('startDate');
                
                if (leaveType === 'Half Leave') {
                    endDateGroup.style.display = 'none';
                    startDateGroup.style.display = 'none';
                    
                    // Force today's date
                    const today = new Date();
                    // Format YYYY-MM-DD taking local timezone offset into account
                    const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
                    
                    startDateInput.value = todayStr;
                    endDateInput.value = todayStr;
                } else {
                    endDateGroup.style.display = 'block';
                    startDateGroup.style.display = 'block';
                }
            }
        </script>
        <?php endif; ?>

        <!-- View Leave Details Modal -->
        <div id="viewLeaveModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:20px; border-radius:8px; width:400px; max-width:90%;">
                <h3>Leave Details for <span id="viewLeaveStudentName" style="color:var(--primary);"></span></h3>
                <div style="margin-top:15px; font-size:15px; line-height:1.6;">
                    <p><strong>Type:</strong> <span id="viewLeaveType"></span></p>
                    <p><strong>Dates:</strong> <span id="viewLeaveDates"></span></p>
                    <p><strong>Remarks:</strong> <span id="viewLeaveRemarks"></span></p>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('viewLeaveModal').style.display='none'">Close</button>
                </div>
            </div>
        </div>
        <script>
            function viewLeaveDetails(studentName, leaveType, dateRange, remarks) {
                document.getElementById('viewLeaveStudentName').textContent = studentName;
                document.getElementById('viewLeaveType').textContent = leaveType;
                document.getElementById('viewLeaveDates').textContent = dateRange || 'N/A';
                document.getElementById('viewLeaveRemarks').textContent = remarks || 'No remarks provided.';
                document.getElementById('viewLeaveModal').style.display = 'flex';
            }
        </script>
    </div>
<?php endif; ?>
