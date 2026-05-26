<?php
/**
 * Monthly Attendance Report View
 * Variables: $classes, $filterClass, $classDetails, $yearMonth, $students, $pageTitle
 */
?>

<style>
/* =========================
   MODERN MONTHLY ATTENDANCE UI
========================= */

:root{
    --attendance-bg: #f4f7fb;
    --attendance-card: #ffffff;
    --attendance-border: #e5e7eb;
    --attendance-shadow: 0 10px 25px rgba(0,0,0,0.06);
    --attendance-radius: 18px;
}

/* Page */
body{
    background: var(--attendance-bg);
}

/* Header */
.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:25px;
    padding:25px;
    border-radius:24px;
    background: linear-gradient(135deg,#2563eb 0%, #4f46e5 100%);
    color:#fff;
    box-shadow: 0 15px 35px rgba(37,99,235,0.18);
}

.page-header h1{
    margin:0;
    font-size:32px;
    font-weight:700;
    letter-spacing:-0.5px;
}

.page-header p{
    margin-top:8px;
    opacity:0.9;
    font-size:14px;
}

/* Buttons */
.btn{
    border:none;
    border-radius:14px;
    padding:12px 18px;
    font-weight:600;
    transition:0.3s ease;
    cursor:pointer;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.btn:hover{
    transform:translateY(-2px);
}

.btn-primary{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:white;
    box-shadow:0 10px 20px rgba(37,99,235,0.25);
}

.btn-secondary{
    background:rgba(255,255,255,0.15);
    color:white;
    backdrop-filter: blur(8px);
}

.btn-success{
    background:linear-gradient(135deg,#10b981,#059669);
    color:white;
    box-shadow:0 10px 20px rgba(16,185,129,0.22);
}

/* Filter Bar */
.filter-bar{
    display:flex;
    gap:20px;
    align-items:end;
    flex-wrap:wrap;
    padding:25px;
    background:var(--attendance-card);
    border-radius:var(--attendance-radius);
    box-shadow:var(--attendance-shadow);
    margin-bottom:25px;
    border:1px solid var(--attendance-border);
}

.filter-group label{
    display:block;
    margin-bottom:8px;
    font-size:13px;
    font-weight:700;
    color:#4b5563;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

.form-control{
    width:100%;
    border:1px solid #d1d5db;
    border-radius:14px;
    padding:12px 14px;
    font-size:15px;
    background:#fff;
    transition:0.25s ease;
}

.form-control:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,0.12);
    outline:none;
}

/* Stats */
.stats-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:20px;
}

.stat-card{
    background:var(--attendance-card);
    border-radius:22px;
    padding:24px;
    box-shadow:var(--attendance-shadow);
    border:1px solid var(--attendance-border);
    display:flex;
    align-items:center;
    gap:18px;
    transition:0.3s ease;
    overflow:hidden;
    position:relative;
}

.stat-card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:5px;
    height:100%;
    background:linear-gradient(to bottom,#2563eb,#4f46e5);
}

.stat-card:hover{
    transform:translateY(-4px);
}

.stat-icon{
    width:65px;
    height:65px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#eff6ff;
    font-size:24px;
}

.stat-details h3{
    margin:0;
    font-size:24px;
    font-weight:700;
    color:#111827;
}

.stat-details p{
    margin-top:6px;
    color:#6b7280;
    font-size:14px;
}

/* Table Container */
.table-container{
    background:white;
    border-radius:22px;
    overflow:hidden;
    box-shadow:var(--attendance-shadow);
    border:1px solid var(--attendance-border);
}

/* Table Header */
.table-header{
    padding:22px 24px;
    background:linear-gradient(135deg,#111827,#1f2937);
    color:white;
}

.table-header h2{
    margin:0;
    font-size:22px;
    font-weight:700;
}

/* Table */
.data-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    font-size:14px;
}

.data-table thead th{
    background:#1e3a8a;
    color:white;
    font-weight:700;
    border:none;
    padding:14px 10px;
    white-space:nowrap;
}

.data-table tbody tr{
    transition:0.2s ease;
}

.data-table tbody tr:nth-child(even){
    background:#f9fafb;
}

.data-table tbody tr:hover{
    background:#eef4ff;
}

.data-table td{
    border-bottom:1px solid #f1f5f9;
    padding:12px 8px;
}

/* Sticky columns */
.data-table td:first-child,
.data-table th:first-child{
    box-shadow:4px 0 12px rgba(0,0,0,0.04);
}

.data-table td:nth-child(2),
.data-table th:nth-child(2){
    box-shadow:4px 0 12px rgba(0,0,0,0.03);
}

/* Attendance Cells */
.attendance-present{
    background:#dcfce7 !important;
    color:#166534 !important;
    border-radius:8px;
}

.attendance-absent{
    background:#fee2e2 !important;
    color:#991b1b !important;
    border-radius:8px;
}

.attendance-leave{
    background:#fef3c7 !important;
    color:#92400e !important;
    border-radius:8px;
}

.attendance-half{
    background:#dbeafe !important;
    color:#1d4ed8 !important;
    border-radius:8px;
}

/* Legend */
.form-card{
    border:1px solid var(--attendance-border);
}

/* Badges */
.badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:28px;
    height:28px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    padding:0 10px;
}

.badge-present{
    background:#dcfce7;
    color:#166534;
}

.badge-absent{
    background:#fee2e2;
    color:#991b1b;
}

.badge-pending{
    background:#fef3c7;
    color:#92400e;
}

.badge-excused{
    background:#dbeafe;
    color:#1d4ed8;
}

/* Empty State */
.empty-state{
    text-align:center;
    padding:70px 20px;
    background:white;
    border-radius:22px;
    box-shadow:var(--attendance-shadow);
}

.empty-icon{
    font-size:60px;
    color:#9ca3af;
    margin-bottom:15px;
}

/* Scrollbar */
.table-container::-webkit-scrollbar{
    height:10px;
}

.table-container::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:999px;
}

.table-container::-webkit-scrollbar-track{
    background:#f1f5f9;
}

/* Responsive */
@media(max-width:768px){

    .page-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .page-header h1{
        font-size:24px;
    }

    .filter-bar{
        padding:18px;
    }

    .stat-card{
        padding:18px;
    }

    .table-header h2{
        font-size:18px;
    }

    .data-table{
        font-size:12px;
    }

    .btn{
        width:100%;
        justify-content:center;
    }
}
</style>

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

<form method="GET" class="filter-bar">
    <input type="hidden" name="module" value="admin">
    <input type="hidden" name="action" value="attendance/monthly">
    
    <div class="filter-group" style="flex:1; min-width:180px;">
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
    
    <div class="filter-group" style="flex:1; min-width:180px;">
        <label>Month</label>
        <input type="month" name="month" class="form-control" value="<?php echo htmlspecialchars($yearMonth); ?>" required max="<?php echo date('Y-m'); ?>">
    </div>
    
    <div class="filter-group">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Load Report
        </button>
    </div>
</form>

<?php if ($filterClass && !empty($students)):
    $monthName = date('F Y', strtotime($yearMonth . '-01'));
    $className = $classDetails ? htmlspecialchars($classDetails['class_name'] . ' ' . $classDetails['section']) : 'Class #' . $filterClass;
    $totalStudents = count($students);
    $daysInMonthCalc = (int)date('t', strtotime($yearMonth . '-01'));
    $workingDays = 0;
    for ($d = 1; $d <= $daysInMonthCalc; $d++) {
        if (date('N', strtotime($yearMonth . '-' . sprintf('%02d', $d))) < 6) $workingDays++;
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
            <form method="POST" action="<?php echo moduleUrl('admin', 'attendance/monthly'); ?>" class="no-auto-validate" style="display:inline;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="class_id" value="<?php echo $filterClass; ?>">
                <input type="hidden" name="month" value="<?php echo htmlspecialchars($yearMonth); ?>">
                <button type="submit" class="btn btn-success"><i class="fas fa-file-csv"></i> Export CSV</button>
            </form>
        </div>

        <div class="table-container" style="overflow-x: auto;">
            <div class="table-header">
                <h2>Monthly Attendance Grid</h2>
            </div>
            <table class="data-table" style="min-width: 1200px;">
                <thead>
                    <tr>
                        <th style="min-width: 60px; position: sticky; left: 0; z-index: 2;">Roll No</th>
                        <th style="min-width: 150px; position: sticky; left: 60px; z-index: 2;">Name</th>
                        <?php 
                        $daysInMonth = (int)date('t', strtotime($yearMonth . '-01'));
                        for ($i = 1; $i <= $daysInMonth; $i++): 
                            $isWeekend = date('N', strtotime($yearMonth . '-' . sprintf('%02d', $i))) >= 6;
                        ?>
                            <th style="text-align:center; padding: 5px; min-width: 35px; <?php echo $isWeekend ? 'background-color: var(--primary-dark); opacity: 0.8;' : ''; ?>">
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
                                $status = $student['attendance'][$i] ?? null;
                                $cellClass = '';
                                $text = '-';
                                if ($status === 'Present') { $cellClass = 'attendance-present'; $text = 'P'; }
                                elseif ($status === 'Absent') { $cellClass = 'attendance-absent'; $text = 'A'; }
                                elseif ($status === 'Leave') { $cellClass = 'attendance-leave'; $text = 'L'; }
                                elseif ($status === 'Half Leave') { $cellClass = 'attendance-half'; $text = 'HL'; }
                            ?>
                                <td style="text-align:center; padding: 5px; font-weight: bold;" class="<?php echo $cellClass; ?>">
                                    <?php echo $text; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
