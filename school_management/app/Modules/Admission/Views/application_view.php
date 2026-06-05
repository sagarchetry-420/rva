<?php
/**
 * View Application Details
 * Variables: $pageTitle, $application
 */
?>
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <div>
        <h1><i class="fa-solid fa-address-card"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Viewing details for APP-<?php echo str_pad($application['id'], 4, '0', STR_PAD_LEFT); ?></p>
    </div>
    <div>
        <a href="<?php echo moduleUrl('admin', 'applications'); ?>" class="btn btn-primary" style="padding: 10px 18px; border: none; border-radius: 6px; color: white; text-decoration: none; font-weight: 600; background: var(--primary); box-shadow: 0 4px 6px rgba(128,0,0,0.2); transition: all 0.2s;"><i class="fa-solid fa-arrow-left" style="margin-right: 5px;"></i> Back to List</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
    <!-- Left Column: Main Details -->
    <div style="display: flex; flex-direction: column; gap: 25px;">
        <!-- Student Information Card -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 16px; color: #111827;"><i class="fa-solid fa-user-graduate" style="color: var(--primary); margin-right: 8px;"></i> Student Information</h3>
                <?php 
                    $status = $application['status'] ?? 'pending';
                    $badgeColor = 'var(--warning)';
                    if ($status === 'approved') $badgeColor = 'var(--info)';
                    if ($status === 'enrolled') $badgeColor = 'var(--success)';
                    if ($status === 'rejected') $badgeColor = 'var(--danger)';
                ?>
                <span style="background: <?php echo $badgeColor; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
            <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Full Name</span>
                    <strong style="color: #1f2937; font-size: 16px;"><?php echo htmlspecialchars($application['student_name']); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Applied For Class</span>
                    <strong style="color: #1f2937; font-size: 16px;"><?php echo htmlspecialchars($application['class_name'] . ' ' . $application['section']); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Date of Birth</span>
                    <strong style="color: #1f2937; font-size: 15px;"><?php echo htmlspecialchars(date('d M Y', strtotime($application['date_of_birth']))); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Gender</span>
                    <strong style="color: #1f2937; font-size: 15px;"><?php echo htmlspecialchars(ucfirst($application['gender'] ?? 'N/A')); ?></strong>
                </div>
            </div>
        </div>

        <!-- Contact & Parent Information Card -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 16px; color: #111827;"><i class="fa-solid fa-users" style="color: var(--primary); margin-right: 8px;"></i> Contact & Parent Details</h3>
            </div>
            <div style="padding: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Parent/Guardian Name</span>
                    <strong style="color: #1f2937; font-size: 15px;"><?php echo htmlspecialchars($application['parent_name'] ?? 'N/A'); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Parent Phone Number</span>
                    <strong style="color: #1f2937; font-size: 15px;"><a href="tel:<?php echo htmlspecialchars($application['parent_phone'] ?? ''); ?>" style="color: var(--primary); text-decoration: none;"><i class="fa-solid fa-phone" style="font-size: 12px;"></i> <?php echo htmlspecialchars($application['parent_phone'] ?? 'N/A'); ?></a></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Applicant Phone Number</span>
                    <strong style="color: #1f2937; font-size: 15px;"><?php echo htmlspecialchars($application['phone'] ?? 'N/A'); ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Email Address</span>
                    <strong style="color: #1f2937; font-size: 15px;"><?php echo !empty($application['email']) ? htmlspecialchars($application['email']) : '<span style="color:#9ca3af; font-style:italic;">Not provided</span>'; ?></strong>
                </div>
                <div style="grid-column: 1 / -1;">
                    <span style="display: block; font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Residential Address</span>
                    <strong style="color: #1f2937; font-size: 15px; display: block; line-height: 1.5;"><?php echo !empty($application['address']) ? nl2br(htmlspecialchars($application['address'])) : '<span style="color:#9ca3af; font-style:italic;">Not provided</span>'; ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Sidebar Actions & Documents -->
    <div style="display: flex; flex-direction: column; gap: 25px;">
        
        <!-- Application Timeline / Actions -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 16px; color: #111827;"><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary); margin-right: 8px;"></i> Application Status</h3>
            </div>
            <div style="padding: 20px;">
                <p style="margin: 0 0 15px 0; font-size: 14px; color: #4b5563;">
                    <strong>Submitted:</strong> <?php echo date('d M Y, h:i A', strtotime($application['created_at'])); ?><br>
                    <span style="color: #9ca3af; font-size: 12px;">(<?php echo time() - strtotime($application['created_at']) < 86400 ? 'Today' : round((time() - strtotime($application['created_at'])) / 86400) . ' days ago'; ?>)</span>
                </p>
                
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if ($status === 'pending'): ?>
                        <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" style="width: 100%; padding: 10px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;"><i class="fas fa-check"></i> Approve to Merit List</button>
                        </form>
                        <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" style="margin:0;" onsubmit="return confirm('Reject this application?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" style="width: 100%; padding: 10px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;"><i class="fas fa-times"></i> Reject Application</button>
                        </form>
                    <?php elseif ($status === 'approved'): ?>
                        <form method="POST" action="<?php echo moduleUrl('admin', 'applications'); ?>" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="enroll">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <button type="submit" style="width: 100%; padding: 10px; background: var(--primary); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s;"><i class="fas fa-user-plus"></i> Officially Enroll Student</button>
                        </form>
                    <?php else: ?>
                        <div style="padding: 10px; background: #f3f4f6; color: #6b7280; text-align: center; border-radius: 6px; font-weight: 500;">
                            No pending actions available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents Card -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; overflow: hidden;">
            <div style="background: #f9fafb; padding: 15px 20px; border-bottom: 1px solid #e5e7eb;">
                <h3 style="margin: 0; font-size: 16px; color: #111827;"><i class="fa-solid fa-folder-open" style="color: var(--primary); margin-right: 8px;"></i> Uploaded Documents</h3>
            </div>
            <div style="padding: 20px;">
                <?php 
                $hasDocs = false;
                if (!empty($application['documents'])) {
                    $docs = json_decode($application['documents'], true);
                    if (is_array($docs) && count($docs) > 0) {
                        $hasDocs = true;
                        echo '<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">';
                        foreach ($docs as $index => $docPath) {
                            $ext = pathinfo($docPath, PATHINFO_EXTENSION);
                            $icon = 'fa-file';
                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) $icon = 'fa-image';
                            if (strtolower($ext) === 'pdf') $icon = 'fa-file-pdf';
                            
                            echo '<li>';
                            echo '<a href="' . baseUrl($docPath) . '" target="_blank" style="display: flex; align-items: center; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #3b82f6; transition: background 0.2s;" onmouseover="this.style.background=\'#eff6ff\'" onmouseout="this.style.background=\'transparent\'">';
                            echo '<i class="fa-solid ' . $icon . '" style="font-size: 20px; margin-right: 15px; color: #9ca3af;"></i>';
                            echo '<span style="flex: 1; font-weight: 500;">Document ' . ($index + 1) . '</span>';
                            echo '<i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px;"></i>';
                            echo '</a>';
                            echo '</li>';
                        }
                        echo '</ul>';
                    } elseif (is_string($application['documents']) && strpos($application['documents'], 'uploads/') !== false) {
                        $hasDocs = true;
                        echo '<a href="' . baseUrl($application['documents']) . '" target="_blank" style="display: flex; align-items: center; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #3b82f6; transition: background 0.2s;" onmouseover="this.style.background=\'#eff6ff\'" onmouseout="this.style.background=\'transparent\'">';
                        echo '<i class="fa-solid fa-file-alt" style="font-size: 20px; margin-right: 15px; color: #9ca3af;"></i>';
                        echo '<span style="flex: 1; font-weight: 500;">View Attached Document</span>';
                        echo '<i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 12px;"></i>';
                        echo '</a>';
                    }
                }
                
                if (!$hasDocs) {
                    echo '<div style="text-align: center; padding: 20px 0; color: #9ca3af;">';
                    echo '<i class="fa-solid fa-folder-minus" style="font-size: 32px; margin-bottom: 10px;"></i>';
                    echo '<p style="margin: 0; font-size: 14px;">No documents were uploaded.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
    </div>
</div>
