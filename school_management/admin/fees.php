<?php
require_once dirname(__DIR__) . '/config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a = $_POST['action'];
    if ($a === 'assign') {
        $sid = intval($_POST['student_id']);
        $type = sanitize($conn, $_POST['fee_type']);
        $amt = floatval($_POST['amount']);
        $due = sanitize($conn, $_POST['due_date']);
        // Check duplicate: same student + fee_type + due_date
        $dup = mysqli_query($conn, "SELECT fee_id FROM fees WHERE student_id=$sid AND fee_type='$type' AND due_date='$due'");
        if (mysqli_num_rows($dup) > 0) {
            setFlashMessage('error', "A '$type' fee with due date $due is already assigned to this student.");
            header('Location: fees.php'); exit();
        }
        mysqli_query($conn, "INSERT INTO fees (student_id,fee_type,amount,due_date,payment_status) VALUES ($sid,'$type',$amt,'$due','Pending')");
        setFlashMessage('success', 'Fee has been assigned to the student successfully!');
        header('Location: fees.php'); exit();
    }
    if ($a === 'pay') {
        $fid = intval($_POST['fee_id']);
        $method = sanitize($conn, $_POST['payment_method']);
        $receipt = sanitize($conn, $_POST['receipt_number']);
        mysqli_query($conn, "UPDATE fees SET payment_status='Paid', payment_date=CURDATE(), payment_method='$method', receipt_number='$receipt' WHERE fee_id=$fid");
        setFlashMessage('success', 'Payment has been recorded successfully!');
        header('Location: fees.php'); exit();
    }
    if ($a === 'delete') {
        mysqli_query($conn, "DELETE FROM fees WHERE fee_id=".intval($_POST['fee_id']));
        setFlashMessage('success', 'Fee record has been deleted successfully.');
        header('Location: fees.php'); exit();
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE f.payment_status='Pending'";
elseif ($filter === 'paid') $where = "WHERE f.payment_status='Paid'";
elseif ($filter === 'overdue') $where = "WHERE f.payment_status='Pending' AND f.due_date < CURDATE()";

$fees = mysqli_query($conn, "SELECT f.*, CONCAT(s.first_name,' ',s.last_name) as student_name, s.roll_number, c.class_name, c.section 
    FROM fees f JOIN students s ON f.student_id=s.student_id LEFT JOIN classes c ON s.class_id=c.class_id $where ORDER BY f.fee_id DESC");

$students_dd = mysqli_query($conn, "SELECT s.student_id, CONCAT(s.first_name,' ',s.last_name,' (',s.roll_number,')') as label FROM students s ORDER BY s.first_name");

// Stats
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE payment_status='Paid'"); $total_collected = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as t FROM fees WHERE payment_status='Pending'"); $total_pending = mysqli_fetch_assoc($r)['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    <div class="main-container">
        <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
        <div class="content">
            <div class="page-header">
                <div><h1><i class="fa-solid fa-money-bill-wave"></i> Fee Management</h1><p>Assign and collect fees</p></div>
                <button class="btn btn-primary" onclick="openModal('assignModal')">+ Assign Fee</button>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon classes-icon"><i class="fa-solid fa-chalkboard"></i></div><div class="stat-details"><h3>₹<?php echo number_format($total_collected); ?></h3><p>Total Collected</p></div></div>
                <div class="stat-card"><div class="stat-icon fees-icon"><i class="fa-solid fa-hourglass-half"></i></div><div class="stat-details"><h3>₹<?php echo number_format($total_pending); ?></h3><p>Total Pending</p></div></div>
            </div>

            <div class="filter-bar">
                <a href="?filter=all" class="btn <?php echo $filter==='all'?'btn-primary':'btn-secondary'; ?> btn-sm">All</a>
                <a href="?filter=pending" class="btn <?php echo $filter==='pending'?'btn-warning':'btn-secondary'; ?> btn-sm">Pending</a>
                <a href="?filter=paid" class="btn <?php echo $filter==='paid'?'btn-success':'btn-secondary'; ?> btn-sm">Paid</a>
                <a href="?filter=overdue" class="btn <?php echo $filter==='overdue'?'btn-danger':'btn-secondary'; ?> btn-sm">Overdue</a>
            </div>

            <div class="table-container">
                <table class="data-table" id="dataTable">
                    <thead><tr><th>Student</th><th>Class</th><th>Fee Type</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php while ($f = mysqli_fetch_assoc($fees)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f['student_name']); ?></strong><br><small><?php echo htmlspecialchars($f['roll_number']); ?></small></td>
                        <td><?php echo htmlspecialchars($f['class_name'].' '.$f['section']); ?></td>
                        <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                        <td><strong>₹<?php echo number_format($f['amount'],2); ?></strong></td>
                        <td><?php echo $f['due_date'] ? date('M d, Y', strtotime($f['due_date'])) : '—'; ?></td>
                        <td><span class="badge badge-<?php echo strtolower($f['payment_status']); ?>"><?php echo $f['payment_status']; ?></span></td>
                        <td class="actions-cell">
                            <?php if ($f['payment_status'] !== 'Paid'): ?>
                            <button  class="btn btn-sm btn-success" onclick="openPayModal(<?php echo $f['fee_id']; ?>)"><i class="fa-solid fa-credit-card"></i> Pay</button>
                            <?php else: ?>
                            <small>Receipt: <?php echo htmlspecialchars($f['receipt_number']); ?></small>
                            <?php endif; ?>
                            <form method="POST" style="display:inline" onsubmit="return confirmDelete()"><input type="hidden" name="action" value="delete"><input type="hidden" name="fee_id" value="<?php echo $f['fee_id']; ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can"></i></button></form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assign Fee Modal -->
    <div id="assignModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Assign Fee</h2><span class="close" onclick="closeModal('assignModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="assign"><div class="modal-body">
        <div class="form-group"><label>Student *</label><select name="student_id" required><option value="">Select Student</option><?php while($s=mysqli_fetch_assoc($students_dd)): ?><option value="<?php echo $s['student_id']; ?>"><?php echo htmlspecialchars($s['label']); ?></option><?php endwhile; ?></select></div>
        <div class="form-row">
            <div class="form-group"><label>Fee Type *</label><select name="fee_type" required><option value="Tuition Fee">Tuition Fee</option><option value="Exam Fee">Exam Fee</option><option value="Library Fee">Library Fee</option><option value="Transport Fee">Transport Fee</option><option value="Other">Other</option></select></div>
            <div class="form-group"><label>Amount (₹) *</label><input type="number" name="amount" step="0.01" required></div>
        </div>
        <div class="form-group"><label>Due Date *</label><input type="date" name="due_date" required></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button><button type="submit" class="btn btn-primary">Assign</button></div></form></div></div>

    <!-- Pay Modal -->
    <div id="payModal" class="modal"><div class="modal-content"><div class="modal-header"><h2>Record Payment</h2><span class="close" onclick="closeModal('payModal')">&times;</span></div>
    <form method="POST"><input type="hidden" name="action" value="pay"><input type="hidden" name="fee_id" id="pay_fee_id"><div class="modal-body">
        <div class="form-group"><label>Payment Method *</label><select name="payment_method" required><option value="Cash">Cash</option><option value="UPI">UPI</option><option value="Card">Card</option><option value="Cheque">Cheque</option><option value="Bank Transfer">Bank Transfer</option></select></div>
        <div class="form-group"><label>Receipt Number *</label><input type="text" name="receipt_number" required></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('payModal')">Cancel</button><button type="submit" class="btn btn-success">Confirm Payment</button></div></form></div></div>

    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <script>function openPayModal(id){document.getElementById('pay_fee_id').value=id;openModal('payModal');}</script>
</body>
</html>

