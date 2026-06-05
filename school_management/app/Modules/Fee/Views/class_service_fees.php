<?php
/**
 * Class Service Fees View
 * Variables: $pageTitle, $service, $classes, $feeMap
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-tags"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Leave a field blank to use the base service fee (₹<?php echo number_format((float)($service['fee_amount'] ?? 0), 2); ?>). Enter 0 to explicitly make it free.</p>
    </div>
    <div>
        <a href="<?php echo moduleUrl('admin', 'services'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Services</a>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form method="POST" action="<?php echo moduleUrl('admin', 'service_class_fees'); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Fee Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $class): ?>
                    <?php 
                        $classId = $class['class_id'];
                        $hasOverride = isset($feeMap[$classId]);
                        $currentValue = $hasOverride ? $feeMap[$classId] : '';
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($class['section'] ?? '-'); ?></td>
                        <td>
                            <div style="position:relative; width: 140px;">
                                <span style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#7f8c8d;">₹</span>
                                <input type="number" name="class_fees[<?php echo $classId; ?>]" step="0.01" min="0" value="<?php echo $currentValue; ?>" placeholder="NA" style="width:100%; padding:8px 10px 8px 25px; border:1px solid var(--border); border-radius:var(--radius); font-size:14px; outline:none; transition:border-color 0.2s; background:#fff; color:var(--text);" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Class Fees</button>
        </div>
    </form>
</div>
