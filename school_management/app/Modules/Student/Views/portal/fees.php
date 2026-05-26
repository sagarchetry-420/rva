<?php
/**
 * Student Fees View
 * Variables: $student, $session, $fees
 */
?>
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <div>
            <h1><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
            <p>Your fee records for <?php echo htmlspecialchars($session['session_name'] ?? 'N/A'); ?></p>
        </div>
    </div>
</div>

<?php if (empty($fees)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-receipt"></i></div>
        <p>No fee records found.</p>
    </div>
<?php else: ?>
    <?php
    $totalAmount = 0;
    $totalPaid = 0;
    foreach ($fees as $f) {
        $totalAmount += $f['amount'];
        $totalPaid += $f['paid_amount'];
    }
    $totalBalance = $totalAmount - $totalPaid;
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div class="form-card" style="text-align: center; padding: 20px; border-left: 4px solid #2196F3;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Fees</p>
            <h2 style="margin: 5px 0 0 0; color: #333;"><?php echo number_format($totalAmount, 2); ?></h2>
        </div>
        <div class="form-card" style="text-align: center; padding: 20px; border-left: 4px solid #4CAF50;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Paid</p>
            <h2 style="margin: 5px 0 0 0; color: #4CAF50;"><?php echo number_format($totalPaid, 2); ?></h2>
        </div>
        <div class="form-card" style="text-align: center; padding: 20px; border-left: 4px solid #F44336;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">Total Balance Due</p>
            <h2 style="margin: 5px 0 0 0; color: #F44336;"><?php echo number_format($totalBalance, 2); ?></h2>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Service / Description</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fees as $fee): ?>
                    <?php $balance = $fee['amount'] - $fee['paid_amount']; ?>
                    <tr>
                        <td>
                            <?php 
                            $feeName = $fee['service_name'] ?? $fee['category_name'] ?? 'General Fee';
                            echo '<strong>' . htmlspecialchars($feeName) . '</strong>';
                            if (!empty($fee['remarks'])) {
                                echo '<br><small style="color:var(--gray);">' . htmlspecialchars($fee['remarks']) . '</small>';
                            }
                            ?>
                        </td>
                        <td><?php echo $fee['due_date'] ? date('d M Y', strtotime($fee['due_date'])) : '-'; ?></td>
                        <td><?php echo number_format($fee['amount'], 2); ?></td>
                        <td><?php echo number_format($fee['paid_amount'], 2); ?></td>
                        <td style="color: <?php echo $balance > 0 ? '#F44336' : '#333'; ?>; font-weight: <?php echo $balance > 0 ? 'bold' : 'normal'; ?>;">
                            <?php echo number_format($balance, 2); ?>
                        </td>
                        <td>
                            <?php 
                            $status = $fee['status'];
                            $badgeClass = 'badge ';
                            if ($status === 'Paid') $badgeClass .= 'bg-success';
                            elseif ($status === 'Partial') $badgeClass .= 'bg-warning text-dark';
                            else $badgeClass .= 'bg-danger';
                            ?>
                            <span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                        </td>
                        <td>
                            <?php if (!empty($fee['receipt_number'])): ?>
                                <a href="<?php echo moduleUrl('student', 'receipt'); ?>?receipt_no=<?php echo urlencode($fee['receipt_number']); ?>" target="_blank" class="btn btn-sm btn-info" style="font-size: 12px; padding: 4px 8px;">
                                    <i class="fas fa-download"></i> Receipt
                                </a>
                            <?php else: ?>
                                <span style="color:#999;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
