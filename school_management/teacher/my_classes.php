<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

$my_classes = mysqli_query($conn, "SELECT DISTINCT c.class_id, c.class_name, c.section, c.academic_year, 
    (SELECT COUNT(*) FROM students WHERE class_id=c.class_id) as student_count,
    GROUP_CONCAT(s.subject_name SEPARATOR ', ') as subjects
    FROM class_subjects cs 
    JOIN classes c ON cs.class_id=c.class_id 
    JOIN subjects s ON cs.subject_id=s.subject_id 
    WHERE cs.teacher_id=$tid GROUP BY c.class_id ORDER BY c.class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/teacher.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>📚 My Classes</h1><p>View your assigned classes and students</p></div></div>
            
            <?php while ($c = mysqli_fetch_assoc($my_classes)): ?>
            <div class="dashboard-section">
                <h2><?php echo htmlspecialchars($c['class_name'].' '.$c['section']); ?> <small style="font-weight:400;color:var(--gray)">— <?php echo $c['academic_year']; ?></small></h2>
                <p style="margin-bottom:15px"><strong>Subjects:</strong> <?php echo htmlspecialchars($c['subjects']); ?> | <strong>Students:</strong> <?php echo $c['student_count']; ?></p>
                
                <?php $students = mysqli_query($conn, "SELECT student_id, first_name, last_name, roll_number, phone FROM students WHERE class_id=".$c['class_id']." ORDER BY roll_number"); ?>
                <?php if (mysqli_num_rows($students) > 0): ?>
                <table class="data-table">
                    <thead><tr><th>Roll</th><th>Name</th><th>Phone</th></tr></thead>
                    <tbody>
                    <?php while ($s = mysqli_fetch_assoc($students)): ?>
                    <tr><td><?php echo htmlspecialchars($s['roll_number']); ?></td><td><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></td><td><?php echo htmlspecialchars($s['phone']); ?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:var(--gray)">No students enrolled.</p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
