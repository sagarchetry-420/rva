<?php
/**
 * Public Admission Form View
 */
?>
<div class="auth-container admission-form-container">
    <div class="auth-header" style="text-align: center; margin-bottom: 30px;">
        <div class="auth-logo" style="margin-bottom: 10px;">
            <img src="/RVA/assets/logo/logo_small.png" alt="School Logo" style="max-height: 80px; width: auto;" loading="lazy">
        </div>
        <h1 style="margin-top: 5px; font-size: 28px;"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="text-muted" style="margin-bottom: 20px;">Apply for the upcoming academic session</p>
        <div>
            <a href="<?php echo moduleUrl('public', 'track_application'); ?>" class="btn btn-outline" style="font-size: 14px; padding: 8px 20px; border-radius: 30px; text-decoration: none; border: 2px solid var(--primary); color: var(--primary); display: inline-block; transition: all 0.2s;"><i class="fa-solid fa-magnifying-glass"></i> Track Existing Application</a>
        </div>
    </div>

    <?php if (!empty($settings['instructions'])): ?>
        <div style="margin-bottom: 30px; text-align: left;">
            <h4 style="margin: 0 0 10px; color: var(--primary); font-size: 16px; text-align: left;"><i class="fa-solid fa-circle-info"></i> Important Instructions</h4>
            <div style="font-size: 14px; color: var(--gray); line-height: 1.6; text-align: left;">
                <?php echo nl2br(htmlspecialchars(trim($settings['instructions']))); ?>
            </div>
            <hr style="margin-top: 25px; margin-bottom: 0; border: none; border-top: 1px solid #e2e8f0;">
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo moduleUrl('public', 'admission'); ?>" enctype="multipart/form-data" class="auth-form" style="text-align: left;">
        <?php echo csrf_field(); ?>

        <div class="form-group form-row">
            <div class="form-col">
                <label class="form-label" for="first_name">Applicant First Name <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First Name" maxlength="50" pattern="^[a-zA-Z\s]+$" title="Only alphabets and spaces allowed" required>
                </div>
            </div>
            <div class="form-col">
                <label class="form-label" for="last_name">Applicant Last Name <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last Name" maxlength="50" pattern="^[a-zA-Z\s]+$" title="Only alphabets and spaces allowed" required>
                </div>
            </div>
        </div>

        <div class="form-group form-row">
            <div class="form-col">
                <label class="form-label" for="date_of_birth">Date of Birth <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-calendar"></i>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="form-col">
                <label class="form-label" for="gender">Gender <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-venus-mars"></i>
                    <select id="gender" name="gender" class="form-control" required style="padding-left: 40px; appearance: none;">
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="class_id">Target Class <span style="color: var(--danger);">*</span></label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-chalkboard"></i>
                <select id="class_id" name="class_id" class="form-control" required style="padding-left: 40px; appearance: none;">
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?php echo $cls['class_id']; ?>">
                            <?php echo htmlspecialchars($cls['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group form-row">
            <div class="form-col">
                <label class="form-label" for="phone">Student Phone <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit Phone Number" maxlength="10" minlength="10" pattern="^[0-9]{10}$" title="Please enter exactly a 10 digit phone number" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)" required>
                </div>
            </div>
            <div class="form-col">
                <label class="form-label" for="email">Student Email <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Email Address" maxlength="100" pattern="^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" title="Please enter a valid email address (e.g. user@example.com)" oninput="this.value = this.value.replace(/[^a-zA-Z0-9._@+-]/g, '')" required>
                </div>
            </div>
        </div>

        <h4 style="margin: 25px 0 15px; color: #1e40af; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">Parent/Guardian Details</h4>

        <div class="form-group form-row">
            <div class="form-col">
                <label class="form-label" for="parent_name">Parent Name <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user-group"></i>
                    <input type="text" id="parent_name" name="parent_name" class="form-control" placeholder="Parent/Guardian Name" maxlength="100" pattern="^[A-Za-z\s]+$" title="Please enter only letters and spaces" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
            </div>
            <div class="form-col">
                <label class="form-label" for="parent_phone">Parent Phone <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" id="parent_phone" name="parent_phone" class="form-control" placeholder="10-digit Phone Number" maxlength="10" minlength="10" pattern="^[0-9]{10}$" title="Please enter exactly a 10 digit phone number" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="address">Full Address (Optional)</label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-location-dot" style="top: 20px;"></i>
                <textarea id="address" name="address" class="form-control" placeholder="Enter residential address" rows="3" maxlength="255" style="padding-left: 40px; resize: vertical;"></textarea>
            </div>
        </div>

        <div class="form-group" style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: #fafafa;">
            <label class="form-label" for="documents" style="margin-bottom: 10px;">Upload Supporting Documents (Optional)</label>
            <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" style="display: block;">
            <small class="text-muted" style="display:block; margin-top:8px; font-size:12px;"><i class="fa-solid fa-info-circle"></i> You can select multiple files. (Max 150KB for images, 1.5MB for PDFs).</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 30px; font-size: 16px; padding: 12px;">
            <i class="fa-solid fa-paper-plane"></i> Submit Application
        </button>
    </form>
</div>


