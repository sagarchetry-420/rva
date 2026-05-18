<?php
require_once dirname(__DIR__) . '/config/database.php';
requireStudent();
$uid = getUserId();
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id=$uid"));
$sid = $student['student_id'];

$exams = mysqli_query($conn, "SELECT DISTINCT e.* FROM examinations e JOIN results r ON e.exam_id=r.exam_id WHERE r.student_id=$sid ORDER BY e.start_date DESC");
$sel_exam = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

$results = null; $total_obtained = 0; $total_max = 0;
if ($sel_exam > 0) {
    $results = mysqli_query($conn, "SELECT r.*, s.subject_name, s.subject_code FROM results r JOIN subjects s ON r.subject_id=s.subject_id WHERE r.student_id=$sid AND r.exam_id=$sel_exam ORDER BY s.subject_name");
    $calc = mysqli_query($conn, "SELECT SUM(marks_obtained) as obt, SUM(max_marks) as mx FROM results WHERE student_id=$sid AND exam_id=$sel_exam");
    $c = mysqli_fetch_assoc($calc);
    $total_obtained = $c['obt'] ?? 0;
    $total_max = $c['mx'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>📝 My Results</h1><p>View your examination results</p></div></div>

            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:15px;align-items:flex-end">
                    <div class="filter-group"><label>Select Examination</label>
                        <select name="exam_id" onchange="this.form.submit()"><option value="">-- Choose Exam --</option>
                        <?php while($e=mysqli_fetch_assoc($exams)):?>
                        <option value="<?php echo $e['exam_id'];?>" <?php echo $sel_exam==$e['exam_id']?'selected':'';?>><?php echo htmlspecialchars($e['exam_name']);?></option>
                        <?php endwhile;?></select>
                    </div>
                </form>
            </div>

            <?php if ($results && mysqli_num_rows($results) > 0): ?>
            <div class="table-container">
                <div class="table-header"><h2>Result Card</h2></div>
                <table class="data-table">
                    <thead><tr><th>Subject</th><th>Code</th><th>Marks Obtained</th><th>Max Marks</th><th>Percentage</th><th>Grade</th></tr></thead>
                    <tbody>
                    <?php while($r=mysqli_fetch_assoc($results)): $pct = $r['max_marks'] > 0 ? round(($r['marks_obtained']/$r['max_marks'])*100,1) : 0; ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['subject_name']);?></strong></td>
                        <td><?php echo htmlspecialchars($r['subject_code']);?></td>
                        <td><?php echo $r['marks_obtained'];?></td>
                        <td><?php echo $r['max_marks'];?></td>
                        <td><?php echo $pct;?>%</td>
                        <td><span class="badge badge-<?php echo $r['grade']==='F'?'absent':'paid';?>"><?php echo $r['grade'];?></span></td>
                    </tr>
                    <?php endwhile;?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--light);font-weight:700">
                            <td colspan="2">Total</td>
                            <td><?php echo $total_obtained;?></td>
                            <td><?php echo $total_max;?></td>
                            <td><?php echo $total_max>0?round(($total_obtained/$total_max)*100,1):0;?>%</td>
                            <td><span class="badge badge-paid"><?php echo $total_max>0?calculateGrade($total_obtained,$total_max):'—';?></span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php elseif($sel_exam > 0):?>
            <div class="dashboard-section"><div class="empty-state"><div class="empty-icon">📝</div><p>No results found for this exam.</p></div></div>
            <?php else:?>
            <div class="dashboard-section"><div class="empty-state"><div class="empty-icon">📝</div><p>Select an exam to view your results.</p></div></div>
            <?php endif;?>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
