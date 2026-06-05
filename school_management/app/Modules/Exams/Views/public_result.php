<?php
/**
 * Public Result View
 * Variables: $exams, $classes, $result_data, $error, $roll_number
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Result - Rose Valley Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background-color: var(--bg-color); font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .result-container { background: #fff; padding: 30px 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%; max-width: 600px; }
        .logo-header { text-align: center; margin-bottom: 25px; }
        .logo-header h1 { color: var(--primary); font-size: 24px; margin-bottom: 5px; }
        .logo-header p { color: var(--gray); font-size: 14px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: var(--text-color); }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; transition: border-color 0.2s; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .btn-check { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn-check:hover { filter: brightness(0.9); }
        
        .marksheet { margin-top: 30px; border: 1px solid var(--border); border-radius: 8px; padding: 20px; }
        .ms-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid var(--primary); padding-bottom: 15px; }
        .ms-header h2 { margin: 0 0 5px 0; color: var(--primary); }
        .ms-info { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }
        .ms-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .ms-table th, .ms-table td { border: 1px solid var(--border); padding: 10px; text-align: center; }
        .ms-table th { background: var(--bg-color); font-weight: 600; }
        .ms-table td.subj { text-align: left; font-weight: 500; }
        .ms-summary { background: var(--bg-color); padding: 15px; border-radius: 8px; text-align: center; font-size: 16px; font-weight: 600; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .print-btn { background: var(--success); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; float: right; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="result-container" id="main-container">
    <?php if (!$result_data): ?>
    <div class="logo-header">
        <h1><i class="fa-solid fa-graduation-cap"></i> Rose Valley Academy</h1>
        <p>Check Examination Results</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo moduleUrl('public', 'check-result'); ?>">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Select Examination</label>
            <select name="exam_id" class="form-control" required>
                <option value="">-- Choose Exam --</option>
                <?php foreach ($exams as $e): ?>
                    <option value="<?php echo $e['exam_id']; ?>"><?php echo htmlspecialchars($e['exam_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Select Class</label>
            <select name="class_id" class="form-control" required>
                <option value="">-- Choose Class --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?php echo $c['class_id']; ?>"><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Roll Number</label>
            <input type="text" name="roll_number" class="form-control" placeholder="e.g. 101" pattern="[a-zA-Z0-9\-_]+" title="Only letters, numbers, hyphens, and underscores are allowed" required>
        </div>
        <button type="submit" class="btn-check"><i class="fa-solid fa-magnifying-glass"></i> Check Result</button>
    </form>
    

    <?php else: 
        $stu = $result_data['student'];
        $total_marks = 0; $obtained_marks = 0; $has_failed = false;
    ?>
    <button class="print-btn" onclick="downloadMarksheet()"><i class="fa-solid fa-print"></i> Download / Print</button>
    <div style="clear:both;"></div>
    
    <div class="marksheet" id="marksheet-card">
        <div class="ms-header">
            <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="Rose Valley Academy Logo" style="height: 70px; margin-bottom: 10px; object-fit: contain;">
            <h2 style="margin-top: 0;">Rose Valley Academy</h2>
            <p><strong><?php echo htmlspecialchars($result_data['exam_name']); ?> Marksheet</strong></p>
        </div>
        <div class="ms-info">
            <div>
                <strong>Student Name:</strong> <?php echo htmlspecialchars($stu['first_name'].' '.$stu['last_name']); ?><br>
                <strong>Roll Number:</strong> <?php echo htmlspecialchars($roll_number); ?>
            </div>
            <div style="text-align:right;">
                <strong>Class:</strong> <?php echo htmlspecialchars($result_data['class_name']); ?><br>
                <strong>Date:</strong> <?php echo date('d M Y'); ?>
            </div>
        </div>
        
        <table class="ms-table">
            <thead>
                <tr>
                    <th class="subj">Subject</th>
                    <th>Full Marks</th>
                    <th>Pass Marks</th>
                    <th>Marks Obtained</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($result_data['marks'] as $m): 
                    $fm = $m['full_marks'] ?? 100;
                    $pm = $m['pass_marks'] ?? 30;
                    $total_marks += $fm;
                    
                    if (isset($m['is_absent']) && $m['is_absent']) {
                        $obt = 'ABSENT';
                        $has_failed = true;
                    } else {
                        $obt = $m['marks_obtained'];
                        $obtained_marks += $obt;
                        if ($obt !== 'ABSENT' && $obt < $pm) $has_failed = true;
                    }
                ?>
                <tr>
                    <td class="subj"><?php echo htmlspecialchars($m['subject_name']); ?></td>
                    <td><?php echo $fm; ?></td>
                    <td><?php echo $pm; ?></td>
                    <td <?php echo ((isset($m['is_absent']) && $m['is_absent']) || ($obt!=='ABSENT' && $obt<$pm)) ? 'style="color:red;font-weight:bold"' : ''; ?>><?php echo $obt; ?></td>
                    <td><?php echo $m['grade']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="ms-summary">
            <?php 
            $pct = $total_marks > 0 ? round(($obtained_marks / $total_marks) * 100, 2) : 0;
            $final_status = $has_failed ? "<span style='color:red'>FAIL</span>" : "<span style='color:green'>PASS</span>";
            ?>
            <div style="display:flex; justify-content:space-between;">
                <span><strong>Total:</strong> <?php echo $obtained_marks . ' / ' . $total_marks; ?></span>
                <span><strong>Percentage:</strong> <?php echo $pct; ?>%</span>
                <span><strong>Result:</strong> <?php echo $final_status; ?></span>
            </div>
        </div>
    </div>
    
    <div style="text-align:center; margin-top:20px;">
        <a href="<?php echo moduleUrl('public', 'check-result'); ?>" style="color:var(--primary); text-decoration:none; font-weight:600;"><i class="fa-solid fa-rotate-left"></i> Check Another Result</a>
    </div>
    <?php endif; ?>
</div>

<script>
function downloadMarksheet() {
    var element = document.getElementById('marksheet-card');
    var opt = {
        margin: 0.5,
        filename: 'Marksheet_<?php echo htmlspecialchars($roll_number); ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>
</body>
</html>
