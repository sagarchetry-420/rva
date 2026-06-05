<?php
/**
 * Admission Settings View
 * Variables: $pageTitle, $settings
 */
?>
<div class="page-header">
    <div>
        <h1><i class="fas fa-sliders"></i> <?php echo htmlspecialchars($pageTitle); ?></h1>
        <p>Configure admission portal settings</p>
    </div>
</div>

<div class="form-card" style="max-width:600px; margin-bottom: 20px; background: #eff6ff; border: 1px solid #bfdbfe;">
    <h3 style="margin-top:0; color: #1e40af; font-size: 16px;"><i class="fas fa-link"></i> Public Admission Link</h3>
    <p style="font-size: 14px; color: #3b82f6; margin-bottom: 10px;">Share this link with prospective students to allow them to apply online:</p>
    <div style="display: flex; gap: 10px;">
        <input type="text" id="publicLink" class="form-control" readonly value="<?php echo baseUrl('public/admission'); ?>" style="background: #fff; cursor: pointer;" onclick="this.select();">
        <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('publicLink').value); alert('Link copied to clipboard!');"><i class="fas fa-copy"></i> Copy</button>
    </div>
</div>

<div class="form-card" style="max-width:600px;">
    <form method="POST" action="<?php echo moduleUrl('admin', 'admission-settings'); ?>">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label>Admission Status</label>
            <select name="is_open" class="form-control">
                <option value="1" <?php echo ($settings['is_open'] ?? 0) ? 'selected' : ''; ?>>Open</option>
                <option value="0" <?php echo !($settings['is_open'] ?? 0) ? 'selected' : ''; ?>>Closed</option>
            </select>
        </div>

        <div class="form-group">
            <label>Application Deadline</label>
            <?php 
                $dl = $settings['deadline'] ?? '';
                if (!empty($dl)) {
                    // Convert DD-MM-YYYY to YYYY-MM-DD for the date picker if needed
                    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dl)) {
                        $dl = date('Y-m-d', strtotime($dl));
                    } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dl)) {
                        $dl = date('Y-m-d', strtotime(str_replace('/', '-', $dl)));
                    } else {
                        // Attempt standard parse for any other valid format
                        $time = strtotime($dl);
                        if ($time) $dl = date('Y-m-d', $time);
                    }
                }
                
                // Set the minimum selectable date to today, unless the currently saved date is already in the past
                $today = date('Y-m-d');
                $minDate = (!empty($dl) && $dl < $today) ? $dl : $today;
            ?>
            <input type="date" name="deadline" class="form-control" min="<?php echo $minDate; ?>" value="<?php echo htmlspecialchars($dl); ?>">
        </div>

        <div class="form-group">
            <label>Maximum Applications</label>
            <input type="number" name="max_applications" class="form-control" value="<?php echo (int)($settings['max_applications'] ?? 100); ?>" min="1">
        </div>

        <div class="form-group">
            <label>Instructions for Applicants</label>
            <textarea name="instructions" class="form-control" rows="5" placeholder="Enter any instructions for applicants..."><?php echo htmlspecialchars($settings['instructions'] ?? ''); ?></textarea>
        </div>

        <div style="text-align:right; margin-top:15px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        </div>
    </form>
</div>
