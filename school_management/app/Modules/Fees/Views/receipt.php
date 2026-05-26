<?php
/**
 * Fee Receipt View (Printable)
 * Variables: $receipt, $pageTitle
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rose Valley Academy - Fee Receipt</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f9fafb; margin: 0; padding: 20px; }
        .receipt-card { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 5px solid #2563eb; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px dashed #e5e7eb; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1e3a8a; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .info-group label { display: block; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: bold; margin-bottom: 4px; }
        .info-group div { font-size: 16px; color: #111827; font-weight: 500; }
        .fee-details { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .fee-details th, .fee-details td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .fee-details th { background: #f3f4f6; color: #374151; font-weight: bold; }
        .total-row td { font-weight: bold; font-size: 18px; color: #111827; }
        .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 15px; }
        .print-btn { display: block; width: 100%; max-width: 200px; margin: 20px auto 0; padding: 10px; background: #2563eb; color: #fff; text-align: center; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 16px; font-weight: bold; }
        @page { size: auto; margin: 0mm; } /* Hides browser header/footer like URL and Title */
        @media print {
            body { background: #fff; padding: 20px; }
            .receipt-card { box-shadow: none; border-top: 2px solid #000; padding: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="header">
        <img src="<?php echo BASE_URL; ?>/assets/img/logo.png" alt="Rose Valley Academy Logo" style="height: 80px; margin-bottom: 10px; object-fit: contain;">
        <h1>Rose Valley Academy</h1>
        <p>Official Fee Receipt</p>
        <p style="margin-top:10px; font-weight:bold; color:#059669;">Receipt No: <?php echo htmlspecialchars($receipt['receipt_number']); ?></p>
    </div>

    <div class="info-grid">
        <div class="info-group">
            <label>Student Name</label>
            <div><?php echo htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']); ?></div>
        </div>
        <div class="info-group">
            <label>Roll Number / Class</label>
            <div><?php echo htmlspecialchars($receipt['roll_number']); ?> (<?php echo htmlspecialchars($receipt['class_name'] . ' ' . $receipt['section']); ?>)</div>
        </div>
        <div class="info-group">
            <label>Payment Date</label>
            <div><?php echo date('d M Y', strtotime($receipt['payment_date'])); ?></div>
        </div>
        <div class="info-group">
            <label>Payment Method</label>
            <div><?php echo htmlspecialchars($receipt['payment_method']); ?></div>
        </div>
    </div>

    <table class="fee-details">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align:right;">Amount (INR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php echo htmlspecialchars($receipt['category_name']); ?>
                    <?php if ($receipt['service_name']): ?>
                        <br><small style="color:#6b7280;">Service: <?php echo htmlspecialchars($receipt['service_name']); ?></small>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;"><?php echo number_format($receipt['amount'], 2); ?></td>
            </tr>
            <tr class="total-row">
                <td style="text-align:right;">Total Paid:</td>
                <td style="text-align:right;">₹ <?php echo number_format($receipt['amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Remarks intentionally hidden on PDF -->

    <div style="display:flex; justify-content:space-between; margin-top:40px;">
        <div style="text-align:center;">
            <div style="border-bottom:1px solid #000; width:150px; margin-bottom:5px;"></div>
            <span style="font-size:12px; color:#6b7280;">Student/Parent Signature</span>
        </div>
        <div style="text-align:center;">
            <div style="border-bottom:1px solid #000; width:150px; margin-bottom:5px; height:20px; font-weight:bold; color:#111827; display:flex; align-items:end; justify-content:center;">
                <?php echo htmlspecialchars($receipt['received_by'] ?? 'Admin'); ?>
            </div>
            <span style="font-size:12px; color:#6b7280;">Authorized Signatory</span>
        </div>
    </div>

    <div class="footer">
        <p>This is a computer-generated receipt and does not require a physical signature.</p>
        <p>Thank you for choosing Rose Valley Academy!</p>
    </div>
</div>

<button class="print-btn" onclick="window.print()">Print Receipt</button>

</body>
</html>
