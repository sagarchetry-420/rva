<?php
/**
 * Application Receipt View (Printable)
 * Variables: $application, $pageTitle
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Application Receipt - APP-<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; padding: 20px; color: #111827; }
        .receipt-card { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-top: 5px solid #1a73e8; }
        .header { text-align: center; border-bottom: 2px dashed #e5e7eb; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1a73e8; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #6b7280; font-size: 14px; }
        
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .details-grid div { background: #f9fafb; padding: 10px; border-radius: 4px; }
        .details-grid div span { display: block; font-size: 12px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; }
        .details-grid div strong { font-size: 15px; color: #111827; }
        
        .app-id-box { text-align: center; background: #e8f0fe; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
        .app-id-box h2 { margin: 0; color: #1a73e8; letter-spacing: 2px; }
        .app-id-box p { margin: 5px 0 0 0; font-size: 12px; color: #555; }
        
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 20px; }
        .print-btn { display: block; width: 200px; margin: 30px auto 0; padding: 10px; background: #1a73e8; color: #fff; text-align: center; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .print-btn:hover { background: #1557b0; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-card { box-shadow: none; border-top: 2px solid #000; padding: 0; max-width: 100%; }
            .print-btn { display: none; }
            .app-id-box { background: transparent; border: 2px solid #000; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="header">
        <div style="margin-bottom: 10px;">
            <img src="<?php echo asset('img/logo.png'); ?>" alt="School Logo" style="height: 80px; width: 80px; border-radius: 50%; object-fit: cover;">
        </div>
        <h1>Rose Valley Academy</h1>
        <p>Official Admission Application Receipt</p>
        <p style="margin-top:10px;">Date: <?php echo date('d M Y, h:i A', strtotime($application['created_at'])); ?></p>
    </div>
    
    <div class="app-id-box">
        <h2>APP-<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></h2>
        <p>Please present this ID at the admission office</p>
    </div>

    <div class="details-grid">
        <div>
            <span>Applicant Name</span>
            <strong><?php echo htmlspecialchars($application['student_name']); ?></strong>
        </div>
        <div>
            <span>Class Applied For</span>
            <strong><?php echo htmlspecialchars($application['class_name'] . ' ' . $application['section']); ?></strong>
        </div>
        <div>
            <span>Contact Number</span>
            <strong><?php echo htmlspecialchars($application['phone']); ?></strong>
        </div>
        <div>
            <span>Date of Birth</span>
            <strong><?php echo htmlspecialchars(date('d M Y', strtotime($application['date_of_birth']))); ?></strong>
        </div>
        <div style="grid-column: 1 / -1;">
            <span>Parent/Guardian Name</span>
            <strong><?php echo htmlspecialchars($application['parent_name']); ?></strong>
        </div>
    </div>
    
    <div class="footer">
        <p>This document is a proof of application submission only and does not guarantee admission.</p>
        <p>Rose Valley Academy - Admissions Office</p>
    </div>
</div>

<button class="print-btn" onclick="window.print()">Print Receipt</button>

</body>
</html>
