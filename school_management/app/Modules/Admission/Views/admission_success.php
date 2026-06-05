<div class="success-container">
    <div class="success-icon">
        <i class="fa-solid fa-circle-check"></i>
    </div>
    <h2>Application Submitted!</h2>
    <p>Thank you, <strong style="color: var(--dark);"><?php echo htmlspecialchars($application['student_name']); ?></strong>! Your admission application has been successfully received.</p>
    
    <p style="margin-top: 20px;">Your unique Application Tracking ID is:</p>
    <div class="app-id">APP-<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></div>
    
    <p>Please save this ID or download the receipt. You will need to present it when you visit the school for further processing.</p>
    
    <div class="btn-action-group">
        <a href="<?php echo moduleUrl('public', 'application_receipt'); ?>?id=<?php echo $application['id']; ?>" class="btn btn-primary" target="_blank">
            <i class="fa-solid fa-download"></i> Download Receipt
        </a>
        <a href="<?php echo moduleUrl('public', 'track_application'); ?>" class="btn btn-outline" style="border: 2px solid var(--primary); color: var(--primary);">
            <i class="fa-solid fa-magnifying-glass"></i> Track Status
        </a>
    </div>
    
    
</div>
