<?php
require_once dirname(__DIR__) . '/config/database.php';
requireTeacher();
$uid = getUserId();
$teacher = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM teachers WHERE user_id=$uid"));
$tid = $teacher['teacher_id'];

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$timetable = [];
$tt = mysqli_query($conn, "SELECT t.*, s.subject_name, c.class_name, c.section FROM timetable t JOIN subjects s ON t.subject_id=s.subject_id JOIN classes c ON t.class_id=c.class_id WHERE t.teacher_id=$tid ORDER BY t.period_number");
while ($r = mysqli_fetch_assoc($tt)) $timetable[$r['day_of_week']][$r['period_number']] = $r;

$period_times = [1=>['09:00','09:45'],2=>['09:45','10:30'],3=>['10:45','11:30'],4=>['11:30','12:15'],5=>['13:00','13:45'],6=>['13:45','14:30'],7=>['14:30','15:15']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>🕐 My Timetable</h1><p>Your weekly teaching schedule</p></div></div>
            <div class="table-container" style="overflow-x:auto">
                <table class="timetable-table">
                    <thead><tr><th>Day</th><?php for($p=1;$p<=7;$p++):?><th>P<?php echo $p;?><br><small><?php echo $period_times[$p][0].'-'.$period_times[$p][1];?></small></th><?php endfor;?></tr></thead>
                    <tbody>
                    <?php foreach($days as $day):?><tr><td style="font-weight:700;background:var(--light)"><?php echo $day;?></td>
                    <?php for($p=1;$p<=7;$p++):?><td><?php if(isset($timetable[$day][$p])):$sl=$timetable[$day][$p];?><div class="timetable-cell"><div class="subject-name"><?php echo htmlspecialchars($sl['subject_name']);?></div><div class="teacher-name"><?php echo htmlspecialchars($sl['class_name'].' '.$sl['section']);?></div></div><?php else:?><small style="color:var(--gray)">—</small><?php endif;?></td><?php endfor;?></tr><?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
