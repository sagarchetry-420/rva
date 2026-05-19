<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if (!isset($_GET['student_id']) || !isset($_GET['exam_id'])) {
    die("Student ID and Exam ID are required.");
}

$student_id = intval($_GET['student_id']);
$exam_id = intval($_GET['exam_id']);

// Fetch Student Info
$student = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    JOIN classes c ON s.class_id = c.class_id 
    WHERE s.student_id = $student_id
"));

// Fetch Exam Info
$exam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM examinations WHERE exam_id = $exam_id"));

// Fetch Results
$results_q = mysqli_query($conn, "
    SELECT r.*, sub.subject_name, sch.full_marks, sch.pass_marks
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.subject_id
    LEFT JOIN exam_schedules sch ON r.exam_id = sch.exam_id AND sub.subject_id = sch.subject_id AND sch.class_id = {$student['class_id']}
    WHERE r.student_id = $student_id AND r.exam_id = $exam_id
");

$results = [];
$total_obtained = 0;
$total_full = 0;
$passed = true;

while ($row = mysqli_fetch_assoc($results_q)) {
    $results[] = $row;
    if (!$row['is_absent']) {
        $total_obtained += $row['marks_obtained'];
        if ($row['marks_obtained'] < $row['pass_marks']) $passed = false;
    } else {
        $passed = false;
    }
    $total_full += ($row['full_marks'] ?: 100);
}

$percentage = ($total_full > 0) ? ($total_obtained / $total_full) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marksheet - <?php echo htmlspecialchars($student['first_name']); ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; padding: 40px; color: #333; }
        .marksheet-container { border: 5px double #1a237e; padding: 30px; max-width: 800px; margin: auto; position: relative; }
        .header { text-align: center; border-bottom: 2px solid #1a237e; margin-bottom: 20px; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #1a237e; text-transform: uppercase; letter-spacing: 2px; }
        .header p { margin: 5px 0; font-weight: bold; }
        .student-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .info-col { width: 45%; }
        .info-item { margin-bottom: 5px; border-bottom: 1px dotted #ccc; display: flex; justify-content: space-between; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #1a237e; padding: 10px; text-align: center; }
        th { background: #f0f4ff; }
        .subject-td { text-align: left; font-weight: bold; }
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .sign-box { border-top: 1px solid #333; width: 150px; text-align: center; padding-top: 5px; font-size: 12px; }
        .result-summary { margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .result-val { font-size: 20px; font-weight: bold; color: #1a237e; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #1a237e; color: #fff; border: none; cursor: pointer; border-radius: 5px;">Print Marksheet</button>
    </div>

    <div class="marksheet-container">
        <div class="header">
            <h1><?php echo APP_NAME; ?></h1>
            <p>PROGRESS REPORT - <?php echo strtoupper($exam['exam_name']); ?></p>
            <p>Academic Year: <?php echo $exam['academic_year']; ?></p>
        </div>

        <div class="student-info">
            <div class="info-col">
                <div class="info-item"><span>Student Name:</span> <strong><?php echo strtoupper($student['first_name'] . ' ' . $student['last_name']); ?></strong></div>
                <div class="info-item"><span>Roll Number:</span> <strong><?php echo $student['roll_number']; ?></strong></div>
            </div>
            <div class="info-col">
                <div class="info-item"><span>Class:</span> <strong><?php echo $student['class_name'] . ' - ' . $student['section']; ?></strong></div>
                <div class="info-item"><span>Date of Issue:</span> <strong><?php echo date('d-m-Y'); ?></strong></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2">Subject</th>
                    <th colspan="2">Max Marks</th>
                    <th colspan="2">Marks Obtained</th>
                    <th rowspan="2">Grade</th>
                </tr>
                <tr>
                    <th>Full</th>
                    <th>Pass</th>
                    <th>Obtained</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $res): ?>
                <tr>
                    <td class="subject-td"><?php echo htmlspecialchars($res['subject_name']); ?></td>
                    <td><?php echo $res['full_marks'] ?: 100; ?></td>
                    <td><?php echo $res['pass_marks'] ?: 30; ?></td>
                    <td><?php echo $res['is_absent'] ? 'Ab' : $res['marks_obtained']; ?></td>
                    <td>
                        <?php if ($res['is_absent']): ?>
                            <span style="color:red">Absent</span>
                        <?php elseif ($res['marks_obtained'] < $res['pass_marks']): ?>
                            <span style="color:red">Fail</span>
                        <?php else: ?>
                            <span style="color:green">Pass</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $res['grade']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="result-summary">
            <div>
                Total Obtained: <span class="result-val"><?php echo $total_obtained; ?> / <?php echo $total_full; ?></span>
            </div>
            <div>
                Percentage: <span class="result-val"><?php echo number_format($percentage, 2); ?>%</span>
            </div>
            <div>
                Result: <span class="result-val" style="color: <?php echo $passed ? 'green' : 'red'; ?>"><?php echo $passed ? 'PASSED' : 'FAILED'; ?></span>
            </div>
        </div>

        <div class="footer">
            <div class="sign-box">Class Teacher</div>
            <div class="sign-box">Examination Head</div>
            <div class="sign-box">Principal Signature</div>
        </div>
    </div>
</body>
</html>
