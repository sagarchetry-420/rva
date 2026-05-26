<?php
/**
 * Public Admission Success View
 * Variables: $pageTitle, $application
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Rose Valley Academy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a73e8;
            --success: #34a853;
            --bg-color: #f4f7fe;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .success-card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            font-size: 60px;
            color: var(--success);
            margin-bottom: 20px;
        }
        .app-id {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            background: #e8f0fe;
            padding: 10px 20px;
            border-radius: 8px;
            display: inline-block;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
            margin-top: 20px;
        }
        .btn:hover {
            background: #1557b0;
        }
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            margin-left: 10px;
        }
        .btn-outline:hover {
            background: #f4f7fe;
        }
        p {
            color: #555;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Application Submitted!</h2>
        <p>Thank you, <strong><?php echo htmlspecialchars($application['student_name']); ?></strong>! Your admission application has been successfully received.</p>
        
        <p>Your unique Application ID is:</p>
        <div class="app-id">APP-<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></div>
        
        <p>Please save this ID or download the receipt. You will need to present it when you visit the college for further admission processing.</p>
        
        <div>
            <a href="<?php echo moduleUrl('public', 'application_receipt'); ?>?id=<?php echo $application['id']; ?>" class="btn" target="_blank"><i class="fas fa-download"></i> Download Receipt</a>
            <a href="<?php echo moduleUrl('public', 'admission'); ?>" class="btn btn-outline">Back to Form</a>
        </div>
    </div>
</body>
</html>
