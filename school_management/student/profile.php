<?php
require_once dirname(__DIR__) . '/config/database.php';
requireStudent();
$uid = getUserId();
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, c.class_name, c.section, u.username, u.email FROM students s LEFT JOIN classes c ON s.class_id=c.class_id LEFT JOIN users u ON s.user_id=u.user_id WHERE s.user_id=$uid"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>👤 My Profile</h1><p>Your personal information</p></div></div>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo strtoupper(substr($student['first_name'],0,1).substr($student['last_name'],0,1));?></div>
                    <h2><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']);?></h2>
                    <p><?php echo htmlspecialchars($student['class_name'].' '.$student['section']);?> | Roll: <?php echo htmlspecialchars($student['roll_number']);?></p>
                </div>
                <div class="profile-body">
                    <div class="profile-info-grid">
                        <div class="info-item"><label>Username</label><span>@<?php echo htmlspecialchars($student['username']);?></span></div>
                        <div class="info-item"><label>Email</label><span><?php echo htmlspecialchars($student['email']);?></span></div>
                        <div class="info-item"><label>Date of Birth</label><span><?php echo $student['date_of_birth']?date('M d, Y',strtotime($student['date_of_birth'])):'—';?></span></div>
                        <div class="info-item"><label>Gender</label><span><?php echo htmlspecialchars($student['gender']);?></span></div>
                        <div class="info-item"><label>Phone</label><span><?php echo htmlspecialchars($student['phone'] ?? '')?:'-';?></span></div>
                        <div class="info-item"><label>Address</label><span><?php echo htmlspecialchars($student['address'] ?? '')?:'-';?></span></div>
                        <div class="info-item"><label>Parent Name</label><span><?php echo htmlspecialchars($student['parent_name'] ?? '')?:'-';?></span></div>
                        <div class="info-item"><label>Parent Phone</label><span><?php echo htmlspecialchars($student['parent_phone'] ?? '')?:'-';?></span></div>
                        <div class="info-item"><label>Admission Date</label><span><?php echo $student['admission_date']?date('M d, Y',strtotime($student['admission_date'])):'—';?></span></div>
                        <div class="info-item"><label>Roll Number</label><span><?php echo htmlspecialchars($student['roll_number']);?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
