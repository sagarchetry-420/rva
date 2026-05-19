<?php
require_once dirname(__DIR__) . '/config/database.php';
requireStudent();
$uid = getUserId();
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE user_id=$uid"));
$sid = $student['student_id'];

$fees = mysqli_query($conn, "SELECT * FROM fees WHERE student_id=$sid ORDER BY due_date DESC");

$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE student_id=$sid AND payment_status='Paid'");
$total_paid = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE student_id=$sid AND payment_status='Pending'");
$total_pending = mysqli_fetch_assoc($r)['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Fees - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header"><div><h1>💰 My Fees</h1><p>View your fee status and payment history</p></div></div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon classes-icon">✅</div><div class="stat-details"><h3>₹<?php echo number_format($total_paid);?></h3><p>Total Paid</p></div></div>
                <div class="stat-card"><div class="stat-icon fees-icon">⏳</div><div class="stat-details"><h3>₹<?php echo number_format($total_pending);?></h3><p>Pending</p></div></div>
            </div>

            <div class="table-container">
                <div class="table-header"><h2>Fee Records</h2></div>
                <table class="data-table">
                    <thead><tr><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Payment Date</th><th>Method</th><th>Receipt</th></tr></thead>
                    <tbody>
                    <?php if(mysqli_num_rows($fees)>0):?>
                    <?php while($f=mysqli_fetch_assoc($fees)):?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f['fee_type']);?></strong></td>
                        <td>₹<?php echo number_format($f['amount'],2);?></td>
                        <td><?php echo $f['due_date']?date('M d, Y',strtotime($f['due_date'])):'—';?></td>
                        <td><span class="badge badge-<?php echo strtolower($f['payment_status']);?>"><?php echo $f['payment_status'];?></span></td>
                        <td><?php echo $f['payment_date']?date('M d, Y',strtotime($f['payment_date'])):'—';?></td>
                        <td><?php echo htmlspecialchars($f['payment_method'])?:'-';?></td>
                        <td><?php echo htmlspecialchars($f['receipt_number'])?:'-';?></td>
                    </tr>
                    <?php endwhile;?>
                    <?php else:?>
                    <tr><td colspan="7"><div class="empty-state"><p>No fee records found.</p></div></td></tr>
                    <?php endif;?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
