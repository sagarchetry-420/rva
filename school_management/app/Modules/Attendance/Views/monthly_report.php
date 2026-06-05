<?php
/**
 * Monthly Attendance Report View
 * Variables: $classes, $filterClass, $classDetails, $yearMonth, $students, $pageTitle
 */
?>



<?php
/**
 * Monthly Attendance Report View
 * Variables: $classes, $filterClass, $classDetails, $yearMonth, $students, $pageTitle
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>View whole month attendance for a class</p>
    </div>
    <a href="<?php echo moduleUrl('admin', 'attendance'); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Daily Attendance
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="no-auto-validate" style="display:flex; gap:15px; align-items:flex-end; width:100%; flex-wrap:wrap;">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="action" value="attendance/monthly">
        
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
            <label>Month</label>
            <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($yearMonth); ?>" required max="<?php echo date('Y-m'); ?>">
        </div>
        
        <div class="filter-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Load Report
            </button>
        </div>
    </form>
</div>

<?php if ($filterClass && !empty($students)):
    $monthName = date('F Y', strtotime($yearMonth . '-01'));
    $className = $classDetails ? htmlspecialchars($classDetails['class_name'] . ' ' . $classDetails['section']) : 'Class #' . $filterClass;
    $totalStudents = count($students);
    $daysInMonthCalc = (int)date('t', strtotime($yearMonth . '-01'));
    $workingDays = 0;
    for ($d = 1; $d <= $daysInMonthCalc; $d++) {
        if (date('N', strtotime($yearMonth . '-' . sprintf('%02d', $d))) < 7) $workingDays++;
    }
?>
<div class="stats-grid" style="margin-bottom: 25px;">
    <div class="stat-card">
        <div class="stat-icon students-icon"><i class="fas fa-chalkboard" style="color: #2563eb;"></i></div>
        <div class="stat-details">
            <h3 style="font-size:18px;"><?php echo $className; ?></h3>
            <p>Selected Class</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teachers-icon"><i class="fas fa-calendar" style="color: #f59e0b;"></i></div>
        <div class="stat-details">
            <h3 style="font-size:18px;"><?php echo $monthName; ?></h3>
            <p>Report Month</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon classes-icon"><i class="fas fa-users" style="color: #10b981;"></i></div>
        <div class="stat-details">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Total Students</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon subjects-icon"><i class="fas fa-briefcase" style="color: #8b5cf6;"></i></div>
        <div class="stat-details">
            <h3><?php echo $workingDays; ?></h3>
            <p>Working Days</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($filterClass): ?>
    <?php if (empty($students)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
            <p>No students found for this class.</p>
        </div>
    <?php else: ?>
        <div style="text-align: right; margin-bottom: 15px;">
            <form method="POST" action="<?php echo moduleUrl('admin', 'attendance'); ?>" class="no-auto-validate" style="display:inline;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="exportMonthlyCsv">
                <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                <input type="hidden" name="month" value="<?php echo htmlspecialchars($yearMonth); ?>">
                <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</button>
            </form>
            <form method="POST" action="<?php echo moduleUrl('admin', 'attendance'); ?>" class="no-auto-validate" style="display:inline; margin-left: 10px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="exportMonthlyPdf">
                <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                <input type="hidden" name="month" value="<?php echo htmlspecialchars($yearMonth); ?>">
                <button type="submit" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Download PDF</button>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2>Monthly Attendance Grid</h2>
            </div>
            <div style="overflow-x: auto; width: 100%;">
                <table class="data-table" style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th style="min-width: 60px; position: sticky; left: 0; background-color: var(--primary); color: white; z-index: 2;">Roll No</th>
                            <th style="min-width: 150px; position: sticky; left: 60px; background-color: var(--primary); color: white; z-index: 2;">Name</th>
                            <?php 
                            $daysInMonth = (int)date('t', strtotime($yearMonth . '-01'));
                            for ($i = 1; $i <= $daysInMonth; $i++): 
                                $isWeekend = date('N', strtotime($yearMonth . '-' . sprintf('%02d', $i))) == 7;
                            ?>
                                <th style="text-align:center; padding: 12px 5px; min-width: 35px; <?php echo $isWeekend ? 'background-color: var(--warning); color: #fff;' : ''; ?>">
                                    <?php echo $i; ?>
                                </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td style="position: sticky; left: 0; background: white; z-index: 1;"><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                <td style="position: sticky; left: 60px; background: white; z-index: 1;"><?php echo htmlspecialchars($student['name']); ?></td>
                                
                                <?php for ($i = 1; $i <= $daysInMonth; $i++): 
                                    $isWeekend = date('N', strtotime($yearMonth . '-' . sprintf('%02d', $i))) == 7;
                                    $status = $student['attendance'][$i] ?? null;
                                    $cellClass = '';
                                    $text = '-';
                                    if ($status === 'Present') { $cellClass = 'attendance-present'; $text = 'P'; }
                                    elseif ($status === 'Absent') { $cellClass = 'attendance-absent'; $text = 'A'; }
                                    elseif ($status === 'Leave') { $cellClass = 'attendance-leave'; $text = 'L'; }
                                    elseif ($status === 'Half Leave') { $cellClass = 'attendance-half'; $text = 'HL'; }
                                    
                                    $bgStyle = $isWeekend && !$status ? 'background-color: #fca5a5; opacity: 0.5;' : '';
                                ?>
                                    <td style="text-align:center; padding: 12px 5px; font-weight: bold; font-size: 13px; <?php echo $bgStyle; ?>" class="<?php echo $cellClass; ?>">
                                        <?php echo $text; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="filter-bar" style="margin-top: 25px; display: flex; gap: 20px; align-items: center; justify-content: center;">
            <div style="font-weight: 700; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">Legend:</div>
            <div style="display: flex; align-items: center; gap: 8px;"><span class="badge badge-present">P</span> <span style="font-size:14px; color:#4b5563;">Present</span></div>
            <div style="display: flex; align-items: center; gap: 8px;"><span class="badge badge-absent">A</span> <span style="font-size:14px; color:#4b5563;">Absent</span></div>
            <div style="display: flex; align-items: center; gap: 8px;"><span class="badge badge-pending">L</span> <span style="font-size:14px; color:#4b5563;">Leave</span></div>
            <div style="display: flex; align-items: center; gap: 8px;"><span class="badge badge-excused">HL</span> <span style="font-size:14px; color:#4b5563;">Half Leave</span></div>
        </div>

    <?php endif; ?>
<?php endif; ?>
