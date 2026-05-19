<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check if admin is logged in
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    die('Unauthorized access');
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Base query for class-wise or general reports
$where = "WHERE 1=1";
if ($filter === 'pending') $where .= " AND f.payment_status='Pending'";
elseif ($filter === 'paid') $where .= " AND f.payment_status='Paid'";
elseif ($filter === 'overdue') $where .= " AND f.payment_status='Pending' AND f.due_date < CURDATE()";

if ($class_id > 0) $where .= " AND s.class_id=$class_id";

$query = "SELECT f.*, CONCAT(s.first_name,' ',s.last_name) as student_name, s.roll_number, c.class_name, c.section 
          FROM fees f 
          JOIN students s ON f.student_id=s.student_id 
          LEFT JOIN classes c ON s.class_id=c.class_id 
          $where 
          ORDER BY c.class_name, s.first_name, f.due_date DESC";

// --- CSV EXPORT ---
if ($action === 'csv') {
    $result = mysqli_query($conn, $query);
    
    $filename = "fees_report_" . date('Ymd_His') . ".csv";
    if ($class_id > 0) {
        $c_q = mysqli_query($conn, "SELECT class_name, section FROM classes WHERE class_id=$class_id");
        if ($c = mysqli_fetch_assoc($c_q)) $filename = "fees_" . str_replace(' ', '_', $c['class_name']) . "_{$c['section']}_" . date('Ymd') . ".csv";
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fputs($output, "\xEF\xBB\xBF");
    
    fputcsv($output, array('Student Name', 'Roll Number', 'Class', 'Fee Type', 'Amount', 'Due Date', 'Status', 'Payment Date', 'Payment Method', 'Receipt Number'));
    
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array(
            $row['student_name'],
            $row['roll_number'],
            $row['class_name'] . ' ' . $row['section'],
            $row['fee_type'],
            $row['amount'],
            $row['due_date'] ? date('d-M-Y', strtotime($row['due_date'])) : '',
            $row['payment_status'],
            $row['payment_date'] ? date('d-M-Y', strtotime($row['payment_date'])) : '',
            $row['payment_method'],
            $row['receipt_number']
        ));
    }
    fclose($output);
    exit();
}

// --- PRINT / PDF EXPORT ---
if ($action === 'pdf' || $action === 'student_report') {
    
    $is_student = ($action === 'student_report' && $student_id > 0);
    
    if ($is_student) {
        $query = "SELECT f.*, CONCAT(s.first_name,' ',s.last_name) as student_name, s.roll_number, c.class_name, c.section, s.phone 
                  FROM fees f 
                  JOIN students s ON f.student_id=s.student_id 
                  LEFT JOIN classes c ON s.class_id=c.class_id 
                  WHERE f.student_id = $student_id 
                  ORDER BY f.due_date DESC";
    }
    
    $result = mysqli_query($conn, $query);
    $data = [];
    $total_amount = 0;
    $total_paid = 0;
    $total_due = 0;
    $student_info = null;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
        $total_amount += $row['amount'];
        if ($row['payment_status'] === 'Paid') {
            $total_paid += $row['amount'];
        } else {
            $total_due += $row['amount'];
        }
        if ($is_student && !$student_info) {
            $student_info = $row;
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Report - <?php echo APP_NAME; ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 20px; }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 20px; }
        .report-header h1 { margin: 0; color: #1a237e; font-size: 28px; }
        .report-header p { margin: 5px 0 0; color: #666; }
        .student-details { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; flex-wrap: wrap; }
        .student-details div { width: 50%; margin-bottom: 5px; }
        .student-details strong { display: inline-block; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f1f3f5; font-weight: 600; }
        .status-paid { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-overdue { color: #dc3545; font-weight: bold; }
        .summary-box { float: right; width: 300px; border: 1px solid #ddd; border-radius: 5px; padding: 15px; background: #f8f9fa; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; }
        .summary-row.total { font-weight: bold; font-size: 18px; border-top: 1px solid #ccc; padding-top: 10px; margin-top: 5px; color: #dc3545; }
        .clear { clear: both; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .report-header { border-bottom: 1px solid #000; }
            th, td { border: 1px solid #000; }
            .student-details, .summary-box { background: transparent; border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #1a237e; color: #fff; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; font-size: 16px;">
            <svg style="width:16px;height:16px;vertical-align:middle;margin-right:5px" viewBox="0 0 24 24"><path fill="currentColor" d="M18,3H6V7H18M19,12A1,1 0 0,1 18,11A1,1 0 0,1 19,10A1,1 0 0,1 20,11A1,1 0 0,1 19,12M16,19H8V14H16M19,8H5A3,3 0 0,0 2,11V17H6V21H18V17H22V11A3,3 0 0,0 19,8Z" /></svg>
            Print / Save as PDF
        </button>
        <button onclick="window.close()" style="background: #6c757d; color: #fff; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; font-size: 16px; margin-left: 10px;">Close</button>
    </div>

    <div class="report-header">
        <h1><?php echo APP_NAME; ?></h1>
        <p>
            <?php 
            if ($is_student) echo "Student Fee Statement";
            elseif ($class_id > 0) echo "Class Fee Report - " . ($data[0]['class_name'] ?? '') . " " . ($data[0]['section'] ?? '');
            else echo "Comprehensive Fee Report";
            ?>
        </p>
        <p style="font-size: 14px;">Date Generated: <?php echo date('d-M-Y h:i A'); ?></p>
    </div>

    <?php if ($is_student && $student_info): ?>
    <div class="student-details">
        <div><strong>Student Name:</strong> <?php echo htmlspecialchars($student_info['student_name']); ?></div>
        <div><strong>Class:</strong> <?php echo htmlspecialchars($student_info['class_name'] . ' ' . $student_info['section']); ?></div>
        <div><strong>Roll Number:</strong> <?php echo htmlspecialchars($student_info['roll_number']); ?></div>
        <div><strong>Phone:</strong> <?php echo htmlspecialchars($student_info['phone']); ?></div>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <?php if (!$is_student): ?>
                <th>Student</th>
                <th>Class</th>
                <?php endif; ?>
                <th>Fee Type</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Payment Info</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data)): ?>
            <tr><td colspan="<?php echo $is_student ? '5' : '7'; ?>" style="text-align: center;">No fee records found.</td></tr>
            <?php else: ?>
                <?php foreach ($data as $f): ?>
                <tr>
                    <?php if (!$is_student): ?>
                    <td><strong><?php echo htmlspecialchars($f['student_name']); ?></strong><br><small><?php echo htmlspecialchars($f['roll_number']); ?></small></td>
                    <td><?php echo htmlspecialchars($f['class_name'].' '.$f['section']); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($f['fee_type']); ?></td>
                    <td>₹<?php echo number_format($f['amount'], 2); ?></td>
                    <td><?php echo $f['due_date'] ? date('d-M-Y', strtotime($f['due_date'])) : '—'; ?></td>
                    <td class="status-<?php echo strtolower($f['payment_status']); ?>"><?php echo $f['payment_status']; ?></td>
                    <td>
                        <?php if ($f['payment_status'] === 'Paid'): ?>
                            <small>
                                <strong>Date:</strong> <?php echo date('d-M-Y', strtotime($f['payment_date'])); ?><br>
                                <strong>Method:</strong> <?php echo htmlspecialchars($f['payment_method']); ?><br>
                                <strong>Receipt:</strong> <?php echo htmlspecialchars($f['receipt_number']); ?>
                            </small>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-box">
        <div class="summary-row"><span>Total Assigned:</span> <span>₹<?php echo number_format($total_amount, 2); ?></span></div>
        <div class="summary-row" style="color: #28a745;"><span>Total Paid:</span> <span>₹<?php echo number_format($total_paid, 2); ?></span></div>
        <div class="summary-row total"><span>Total Due:</span> <span>₹<?php echo number_format($total_due, 2); ?></span></div>
    </div>
    <div class="clear"></div>

    <div class="footer">
        This is a computer-generated document and does not require a signature.<br>
        &copy; <?php echo date('Y') . ' ' . APP_NAME; ?>
    </div>

    <script>
        // Optional: Auto-print on load if preferred. Currently relying on the manual button to allow user to preview.
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
<?php 
    exit();
}

// Invalid action
header('Location: fees.php');
exit();
