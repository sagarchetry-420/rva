<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

// Default tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'reports';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Mark Leave
    if ($action === 'mark_leave') {
        $class_id = intval($_POST['class_id']);
        $roll_number = sanitize($conn, $_POST['roll_number']);
        $start_date = sanitize($conn, $_POST['leave_start_date']);
        $end_date = sanitize($conn, $_POST['leave_end_date']);
        $leave_type = sanitize($conn, $_POST['leave_type']);
        
        if (empty($end_date) || $leave_type === 'Half Day') {
            $end_date = $start_date;
        }
        $application_note = sanitize($conn, $_POST['application_note']);
        
        // Find student
        $sr = mysqli_query($conn, "SELECT student_id FROM students WHERE class_id=$class_id AND roll_number='$roll_number'");
        if (mysqli_num_rows($sr) === 0) {
            setFlashMessage('error', "No student found in the selected class with Roll Number '$roll_number'.");
            header('Location: attendance.php?tab=leaves'); exit();
        }
        $student_id = mysqli_fetch_assoc($sr)['student_id'];
        $marked_by = getUserId();
        
        // Handle file upload
        $document_path = null;
        if (isset($_FILES['application_document']) && $_FILES['application_document']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(__DIR__) . '/uploads/applications/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['application_document']['name']));
            if (move_uploaded_file($_FILES['application_document']['tmp_name'], $upload_dir . $filename)) {
                $document_path = 'uploads/applications/' . $filename;
            }
        }
        
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        if ($end < $start) {
            setFlashMessage('error', "End date cannot be before start date.");
            header('Location: attendance.php?tab=leaves'); exit();
        }
        
        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
        
        $count = 0;
        foreach ($period as $dt) {
            $date = $dt->format("Y-m-d");
            $doc_val = $document_path ? "'$document_path'" : "NULL";
            
            $chk = mysqli_query($conn, "SELECT attendance_id FROM attendance WHERE student_id=$student_id AND attendance_date='$date'");
            if (mysqli_num_rows($chk) > 0) {
                mysqli_query($conn, "UPDATE attendance SET status='Excused', leave_type='$leave_type', application_note='$application_note', application_document=$doc_val, marked_by=$marked_by WHERE student_id=$student_id AND attendance_date='$date'");
            } else {
                mysqli_query($conn, "INSERT INTO attendance (student_id,class_id,attendance_date,status,leave_type,application_note,application_document,remarks,marked_by) VALUES ($student_id,$class_id,'$date','Excused','$leave_type','$application_note',$doc_val,'Leave marked by admin',$marked_by)");
            }
            $count++;
        }
        
        setFlashMessage('success', "$leave_type leave has been marked successfully for $count day(s)!");
        header('Location: attendance.php?tab=leaves'); exit();
    }
    
    // Export CSV
    if ($action === 'export_csv') {
        $r_class = intval($_POST['report_class_id'] ?? 0);
        $r_roll = sanitize($conn, $_POST['report_roll_number'] ?? '');
        $r_month = intval($_POST['report_month'] ?? 0);
        $r_year = intval($_POST['report_year'] ?? date('Y'));
        
        $where = ["1=1"];
        if ($r_class > 0) $where[] = "a.class_id=$r_class";
        if ($r_roll !== '') $where[] = "s.roll_number='$r_roll'";
        if ($r_month > 0) $where[] = "MONTH(a.attendance_date)=$r_month";
        if ($r_year > 0) $where[] = "YEAR(a.attendance_date)=$r_year";
        
        $w = implode(' AND ', $where);
        $q = "SELECT s.roll_number, s.first_name, s.last_name, c.class_name, c.section, a.attendance_date, a.status, a.leave_type, a.application_note, a.remarks 
              FROM attendance a 
              JOIN students s ON a.student_id=s.student_id 
              JOIN classes c ON a.class_id=c.class_id 
              WHERE $w ORDER BY a.attendance_date, s.roll_number";
        $res = mysqli_query($conn, $q);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Roll No', 'First Name', 'Last Name', 'Class', 'Section', 'Date', 'Status', 'Leave Type', 'Application Note', 'Remarks']);
        while ($row = mysqli_fetch_assoc($res)) fputcsv($output, $row);
        fclose($output);
        exit();
    }
}

// Fetch common data
$classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");

// Report tab data
$r_class = isset($_GET['report_class_id']) ? intval($_GET['report_class_id']) : 0;
$r_roll = isset($_GET['report_roll_number']) ? sanitize($conn, $_GET['report_roll_number']) : '';
$r_month = isset($_GET['report_month']) ? intval($_GET['report_month']) : intval(date('m'));
$r_year = isset($_GET['report_year']) ? intval($_GET['report_year']) : intval(date('Y'));

$report_data = null; $report_summary = null;
if ($tab === 'reports') {
    $where = ["1=1"];
    if ($r_class > 0) $where[] = "a.class_id=$r_class";
    if ($r_roll !== '') $where[] = "s.roll_number='$r_roll'";
    if ($r_month > 0) $where[] = "MONTH(a.attendance_date)=$r_month";
    if ($r_year > 0) $where[] = "YEAR(a.attendance_date)=$r_year";
    $w = implode(' AND ', $where);
    
    $report_data = mysqli_query($conn, "SELECT s.roll_number, s.first_name, s.last_name, c.class_name, c.section, a.attendance_date, a.status, a.leave_type, a.application_note, a.remarks 
        FROM attendance a JOIN students s ON a.student_id=s.student_id JOIN classes c ON a.class_id=c.class_id 
        WHERE $w ORDER BY a.attendance_date DESC, s.roll_number");
    
    // Summary counts
    $report_summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
        COUNT(CASE WHEN a.status='Present' THEN 1 END) as present_count,
        COUNT(CASE WHEN a.status='Absent' THEN 1 END) as absent_count,
        COUNT(CASE WHEN a.status='Late' THEN 1 END) as late_count,
        COUNT(CASE WHEN a.status='Excused' THEN 1 END) as leave_count,
        COUNT(*) as total_count
        FROM attendance a WHERE $w"));
}

// Fetch recent leaves
$recent_leaves = mysqli_query($conn, "SELECT a.*, s.first_name, s.last_name, s.roll_number, c.class_name, c.section 
    FROM attendance a JOIN students s ON a.student_id=s.student_id JOIN classes c ON a.class_id=c.class_id 
    WHERE a.status='Excused' ORDER BY a.attendance_date DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        .tab-nav { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:20px; }
        .tab-btn { padding:12px 24px; background:none; border:none; font-size:14px; font-weight:600; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; color:var(--gray); transition:all 0.2s; }
        .tab-btn:hover { color:var(--primary); }
        .tab-btn.active { color:var(--primary); border-bottom-color:var(--primary); }
        .summary-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:20px; }
        .summary-card { padding:16px; border-radius:10px; text-align:center; }
        .summary-card h3 { font-size:28px; margin:0 0 4px; }
        .summary-card p { font-size:12px; margin:0; opacity:0.8; }
        .sc-present { background:#dcfce7; color:#166534; }
        .sc-absent { background:#fef2f2; color:#991b1b; }
        .sc-late { background:#fef9c3; color:#854d0e; }
        .sc-leave { background:#dbeafe; color:#1e40af; }
        .sc-total { background:#f3f4f6; color:#374151; }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1><i class="fa-solid fa-check-to-slot"></i> Attendance Management</h1><p>View reports and manage leaves</p></div></div>

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <a href="?tab=reports" class="tab-btn <?php echo $tab==='reports'?'active':''; ?>"><i class="fa-solid fa-chart-bar"></i> Reports & Download</a>
                <a href="?tab=leaves" class="tab-btn <?php echo $tab==='leaves'?'active':''; ?>"><i class="fa-solid fa-calendar-check"></i> Leave Management</a>
            </div>

            <?php if ($tab === 'reports'): ?>
            <!-- ===== TAB 1: REPORTS & DOWNLOAD ===== -->
            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;width:100%">
                    <input type="hidden" name="tab" value="reports">
                    <div class="filter-group"><label>Class</label>
                        <select name="report_class_id" onchange="this.form.submit()"><option value="">All Classes</option>
                        <?php mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                        <option value="<?php echo $c['class_id']; ?>" <?php echo $r_class == $c['class_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                        <?php endwhile; ?>
                        </select></div>
                    <div class="filter-group"><label>Month</label>
                        <select name="report_month" onchange="this.form.submit()"><option value="">All Months</option>
                        <?php for ($m=1;$m<=12;$m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $r_month==$m?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                        <?php endfor; ?>
                        </select></div>
                    <div class="filter-group"><label>Year</label>
                        <select name="report_year" onchange="this.form.submit()">
                        <?php for ($y=date('Y');$y>=date('Y')-5;$y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $r_year==$y?'selected':''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                        </select></div>
                    <div class="filter-group"><label>Student (Roll No)</label><input type="text" name="report_roll_number" value="<?php echo htmlspecialchars($r_roll); ?>" placeholder="Optional" style="width:120px" onchange="this.form.submit()"></div>
                </form>
                <form method="POST" style="margin-left:auto;display:flex;gap:8px;">
                    <input type="hidden" name="action" value="export_csv">
                    <input type="hidden" name="report_class_id" value="<?php echo $r_class; ?>">
                    <input type="hidden" name="report_roll_number" value="<?php echo htmlspecialchars($r_roll); ?>">
                    <input type="hidden" name="report_month" value="<?php echo $r_month; ?>">
                    <input type="hidden" name="report_year" value="<?php echo $r_year; ?>">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-file-csv"></i> CSV</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="downloadReportPDF()"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                </form>
            </div>

            <?php if ($report_summary): ?>
            <div class="summary-cards">
                <div class="summary-card sc-total"><h3><?php echo $report_summary['total_count']; ?></h3><p>Total Records</p></div>
                <div class="summary-card sc-present"><h3><?php echo $report_summary['present_count']; ?></h3><p>Present</p></div>
                <div class="summary-card sc-absent"><h3><?php echo $report_summary['absent_count']; ?></h3><p>Absent</p></div>
                <div class="summary-card sc-late"><h3><?php echo $report_summary['late_count']; ?></h3><p>Late</p></div>
                <div class="summary-card sc-leave"><h3><?php echo $report_summary['leave_count']; ?></h3><p>On Leave</p></div>
            </div>
            <?php endif; ?>

            <div class="table-container" id="reportTable">
                <div class="table-header"><h2>Attendance Report</h2></div>
                <table class="data-table">
                    <thead><tr><th>Roll No</th><th>Name</th><th>Class</th><th>Date</th><th>Status</th><th>Leave Type</th><th>Remarks</th></tr></thead>
                    <tbody>
                    <?php if ($report_data && mysqli_num_rows($report_data) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($report_data)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['class_name'].' '.$r['section']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($r['attendance_date'])); ?></td>
                            <td>
                                <?php
                                $bc = 'badge-paid';
                                $display_status = $r['status'];
                                if ($r['status']==='Absent') $bc='badge-pending';
                                elseif ($r['status']==='Late') $bc='badge-overdue';
                                elseif ($r['status']==='Excused') {
                                    $bc='badge-info';
                                    $display_status = ($r['leave_type'] ?? 'Leave');
                                }
                                ?><span class="badge <?php echo $bc; ?>"><?php echo $display_status; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($r['leave_type'] ?? '—'); ?></td>
                            <td><small><?php echo htmlspecialchars($r['remarks'] ?? ''); ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7"><div class="empty-state"><p>No attendance records found for the selected filters.</p></div></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($tab === 'leaves'): ?>
            <!-- ===== TAB 2: LEAVE MANAGEMENT ===== -->
            <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">
                <!-- Mark Leave Form -->
                <div class="table-container" style="align-self:start;">
                    <div class="table-header"><h2><i class="fa-solid fa-calendar-plus"></i> Mark Leave</h2></div>
                    <form method="POST" enctype="multipart/form-data" style="padding:20px;">
                        <input type="hidden" name="action" value="mark_leave">
                        <div class="form-group">
                            <label>Class *</label>
                            <select name="class_id" required>
                                <option value="">Select Class</option>
                                <?php mysqli_data_seek($classes, 0); while ($c = mysqli_fetch_assoc($classes)): ?>
                                <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Roll Number *</label>
                            <input type="text" name="roll_number" required placeholder="e.g. 101">
                        </div>
                        <div class="form-row">
                            <input type="hidden" name="leave_start_date" value="<?php echo date('Y-m-d'); ?>">
                            <div class="form-group" style="width: 100%;">
                                <label>End Date</label>
                                <input type="date" name="leave_end_date" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Leave Type *</label>
                            <select name="leave_type" required>
                                <option value="">Select Type</option>
                                <option value="Full Day">Full Day</option>
                                <option value="Half Day">Half Day</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Application / Reason</label>
                            <textarea name="application_note" rows="3" placeholder="Optional notes..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Upload Application Document</label>
                            <input type="file" name="application_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small style="color:var(--gray)">Optional. Upload student's leave application.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-calendar-check"></i> Mark Leave</button>
                    </form>
                </div>

                <!-- Recent Leaves Table -->
                <div class="table-container">
                    <div class="table-header"><h2>Recent Leaves</h2></div>
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Class</th><th>Date</th><th>Type</th><th>Document</th></tr></thead>
                        <tbody>
                        <?php if (mysqli_num_rows($recent_leaves) > 0): ?>
                            <?php while ($l = mysqli_fetch_assoc($recent_leaves)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($l['first_name'].' '.$l['last_name']); ?></strong><br><small><?php echo htmlspecialchars($l['roll_number']); ?></small></td>
                                <td><?php echo htmlspecialchars($l['class_name'].' '.$l['section']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($l['attendance_date'])); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($l['leave_type'] ?? 'Full Day'); ?></span></td>
                                <td>
                                    <?php if(!empty($l['application_document'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($l['application_document']); ?>" target="_blank" class="btn btn-sm btn-info" style="padding:2px 8px;font-size:11px"><i class="fa-solid fa-download"></i> View</a>
                                    <?php else: ?>
                                        <small style="color:var(--gray)">N/A</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5"><div class="empty-state"><p>No leave records found.</p></div></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>
    function downloadReportPDF() {
        var element = document.getElementById('reportTable');
        var opt = {
            margin: 0.5,
            filename: 'attendance_report.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const leaveType = document.querySelector('select[name="leave_type"]');
        const startDate = document.querySelector('input[name="leave_start_date"]');
        const endDate = document.querySelector('input[name="leave_end_date"]');

        if (leaveType && startDate && endDate) {
            const endDateGroup = endDate.closest('.form-group');

            function updateDateLogic() {
                if (leaveType.value === 'Half Day') {
                    endDateGroup.style.display = 'none';
                    endDate.value = '';
                } else {
                    endDateGroup.style.display = 'block';
                }

                if (startDate.value) {
                    let sDate = new Date(startDate.value);
                    sDate.setDate(sDate.getDate() + 1);
                    let minEnd = sDate.toISOString().split('T')[0];
                    endDate.setAttribute('min', minEnd);
                    
                    if (endDate.value && endDate.value <= startDate.value) {
                        endDate.value = '';
                    }
                }
            }

            leaveType.addEventListener('change', updateDateLogic);
            startDate.addEventListener('change', updateDateLogic);
            updateDateLogic();
        }
    });
    </script>
</body>
</html>
