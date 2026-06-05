<?php
/**
 * Track Application Status View
 */
?>
<div class="auth-container admission-form-container">
    <div class="auth-header" style="text-align: center; margin-bottom: 30px;">
        <div class="auth-logo" style="margin-bottom: 10px;">
            <img src="/RVA/assets/logo/logo_small.png" alt="School Logo" style="max-height: 80px; width: auto;" loading="lazy">
        </div>
        <h1 style="margin-top: 5px; font-size: 28px;"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="text-muted" style="margin-bottom: 20px;">Enter your details to check the status of your admission application</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 25px; text-align: left; background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 4px; color: #b91c1c;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($application)): ?>
        <div class="status-card" style="margin-bottom: 30px; text-align: left; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="background: var(--bg-main, #fcfcfc); padding: 18px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; color: var(--primary); font-weight: 700;">APP-<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></h3>
                <?php 
                    $status = $application['status'] ?? 'pending';
                    $badgeColor = 'var(--primary)'; // Changed from --warning to --primary
                    if ($status === 'approved') $badgeColor = 'var(--info)';
                    if ($status === 'enrolled') $badgeColor = 'var(--success)';
                    if ($status === 'rejected') $badgeColor = 'var(--danger)';
                    $displayStatus = ucfirst($status);
                ?>
                <span style="background: <?php echo $badgeColor; ?>; color: white; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; letter-spacing: 0.5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <?php echo $displayStatus; ?>
                </span>
            </div>
            <div style="padding: 25px 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <span style="display: block; font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">Applicant Name</span>
                    <strong style="color: var(--text-main); font-size: 15px;"><?php echo htmlspecialchars($application['student_name']); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">Class Applied For</span>
                    <strong style="color: var(--text-main); font-size: 15px;"><?php echo htmlspecialchars($application['class_name']); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">Date of Birth</span>
                    <strong style="color: var(--text-main); font-size: 15px;"><?php echo htmlspecialchars(date('d M Y', strtotime($application['date_of_birth']))); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 4px;">Submitted On</span>
                    <strong style="color: var(--text-main); font-size: 15px;"><?php echo htmlspecialchars(date('d M Y', strtotime($application['created_at']))); ?></strong>
                </div>
            </div>
            <?php if ($status === 'pending'): ?>
                <div style="background: rgba(245, 158, 11, 0.1); padding: 15px 20px; border-top: 1px solid rgba(245, 158, 11, 0.2); color: #b45309; font-size: 14px; font-weight: 500;">
                    <i class="fa-solid fa-clock" style="margin-right: 5px;"></i> Your application is currently under review by the admission office.
                </div>
            <?php elseif ($status === 'approved'): ?>
                <div style="background: rgba(59, 130, 246, 0.1); padding: 15px 20px; border-top: 1px solid rgba(59, 130, 246, 0.2); color: #1d4ed8; font-size: 14px; font-weight: 500;">
                    <i class="fa-solid fa-circle-check" style="margin-right: 5px;"></i> Congratulations! Your application has been approved. Please visit the admission office to complete enrollment.
                </div>
            <?php elseif ($status === 'enrolled'): ?>
                <div style="background: rgba(34, 197, 94, 0.1); padding: 15px 20px; border-top: 1px solid rgba(34, 197, 94, 0.2); color: #15803d; font-size: 14px; font-weight: 500;">
                    <i class="fa-solid fa-graduation-cap" style="margin-right: 5px;"></i> You have been successfully enrolled in this institution.
                </div>
            <?php elseif ($status === 'rejected'): ?>
                <div style="background: rgba(239, 68, 68, 0.1); padding: 15px 20px; border-top: 1px solid rgba(239, 68, 68, 0.2); color: #b91c1c; font-size: 14px; font-weight: 500;">
                    <i class="fa-solid fa-circle-xmark" style="margin-right: 5px;"></i> We regret to inform you that your application was not selected for admission.
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="<?php echo moduleUrl('public', 'track_application'); ?>" class="btn btn-primary" style="display: inline-block; padding: 12px 30px; border-radius: 6px; text-decoration: none; color: white; font-weight: 600; box-shadow: 0 4px 15px rgba(128, 0, 0, 0.2);"><i class="fa-solid fa-rotate-right" style="margin-right: 8px;"></i> Track Another Application</a>
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo moduleUrl('public', 'track_application'); ?>" class="auth-form" style="text-align: left;">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label" for="app_id">Application ID <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-hashtag"></i>
                    <input type="text" id="app_id" name="app_id" class="form-control" placeholder="e.g. APP-0007" pattern="^APP-[0-9]{4,}$" title="Please enter your full Application ID (e.g., APP-0007)" value="<?php echo htmlspecialchars(trim($_POST['app_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>
            
            <div class="form-group form-row">
                <div class="form-col">
                    <label class="form-label" for="phone">Registered Phone Number <span style="color: var(--danger);">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit Phone Number" maxlength="10" minlength="10" pattern="^[0-9]{10}$" title="Please enter exactly a 10 digit phone number" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)" value="<?php echo htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>
                <div class="form-col">
                    <label class="form-label" for="dob">Date of Birth <span style="color: var(--danger);">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-calendar"></i>
                        <input type="date" id="dob" name="dob" class="form-control" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars(trim($_POST['dob'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 30px; font-size: 16px; padding: 12px;">
                <i class="fa-solid fa-magnifying-glass"></i> Check Status
            </button>
        </form>
    <?php endif; ?>
    
    
</div>


