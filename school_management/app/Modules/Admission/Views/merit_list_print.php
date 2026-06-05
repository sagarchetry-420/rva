<?php
/**
 * Merit List Print View
 * Variables: $meritList, $classId, $pageTitle
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #000; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0 0 10px 0; font-size: 24px; }
        .header p { margin: 0; font-size: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 10px; text-align: left; font-size: 14px; }
        th { background-color: #f4f4f4; }
        
        .print-btn { display: block; width: 200px; margin: 30px auto 0; padding: 10px; background: #1a73e8; color: #fff; text-align: center; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="header">
    <div style="margin-bottom: 10px;">
        <img src="<?php echo asset('img/logo.png'); ?>" alt="School Logo" style="height: 80px; width: 80px; border-radius: 50%; object-fit: cover;">
    </div>
    <h1>Rose Valley Academy</h1>
    <h2>Official Merit List (Approved Applications)</h2>
    <p>Date: <?php echo date('d M Y'); ?></p>
    <?php if ($classId > 0 && !empty($meritList)): ?>
        <h3 style="margin-top:10px;">Class: <?php echo htmlspecialchars($meritList[0]['class_name'] . ' ' . $meritList[0]['section']); ?></h3>
    <?php endif; ?>
</div>

<?php if (empty($meritList)): ?>
    <p style="text-align:center;">No approved applications found for the selected criteria.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th width="10%">#</th>
                <th width="35%">Student Name</th>
                <th width="20%">Class</th>
                <th width="35%">Parent/Contact</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($meritList as $app): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($app['class_name'] . ' ' . $app['section']); ?></td>
                    <td><?php echo htmlspecialchars($app['parent_name'] . ' (' . $app['phone'] . ')'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<button class="print-btn" onclick="window.print()">Print PDF</button>

</body>
</html>
