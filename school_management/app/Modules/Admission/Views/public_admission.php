<?php
/**
 * Public Admission Form View
 */
?>
<div class="auth-container admission-form-container">
    <div class="auth-header">
        <div class="auth-logo">
            <i class="fa-solid fa-graduation-cap fa-3x" style="color: var(--primary);"></i>
        </div>
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="text-muted">Apply for the upcoming academic session</p>
    </div>

    <?php if (!empty($settings['instructions'])): ?>
        <div class="alert alert-info" style="margin-bottom: 25px; text-align: left; background: #eff6ff; border-left: 4px solid #3b82f6; padding: 15px; border-radius: 4px;">
            <h4 style="margin: 0 0 10px; color: #1e40af; font-size: 15px;"><i class="fa-solid fa-circle-info"></i> Important Instructions</h4>
            <div style="font-size: 14px; color: #3b82f6; white-space: pre-line;">
                <?php echo htmlspecialchars($settings['instructions']); ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo moduleUrl('public', 'admission'); ?>" enctype="multipart/form-data" class="auth-form" style="text-align: left;">
        <?php echo csrf_field(); ?>

        <div class="form-group" style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label class="form-label" for="first_name">Applicant First Name <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First Name" pattern="^[a-zA-Z\s]+$" title="Only alphabets and spaces allowed" required>
                </div>
            </div>
            <div style="flex: 1;">
                <label class="form-label" for="last_name">Applicant Last Name <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last Name" pattern="^[a-zA-Z\s]+$" title="Only alphabets and spaces allowed" required>
                </div>
            </div>
        </div>

        <div class="form-group" style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label class="form-label" for="date_of_birth">Date of Birth <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-calendar"></i>
                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div style="flex: 1;">
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
                            <?php echo htmlspecialchars($cls['class_name'] . ' ' . $cls['section']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group" style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label class="form-label" for="phone">Student Phone <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit Phone Number" maxlength="10" minlength="10" pattern="^[0-9]{10}$" title="Please enter exactly a 10 digit phone number" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)" required>
                </div>
            </div>
            <div style="flex: 1;">
                <label class="form-label" for="email">Student Email</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Optional" pattern="^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$" title="Please enter a valid email address (e.g. user@example.com)" oninput="this.value = this.value.replace(/[^a-zA-Z0-9._@+-]/g, '')">
                </div>
            </div>
        </div>

        <h4 style="margin: 25px 0 15px; color: #1e40af; font-size: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px;">Parent/Guardian Details</h4>

        <div class="form-group" style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <label class="form-label" for="parent_name">Parent Name <span style="color: var(--danger);">*</span></label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-user-group"></i>
                    <input type="text" id="parent_name" name="parent_name" class="form-control" placeholder="Parent/Guardian Name" pattern="^[A-Za-z\s]+$" title="Please enter only letters and spaces" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>
                </div>
            </div>
            <div style="flex: 1;">
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
                <textarea id="address" name="address" class="form-control" placeholder="Enter residential address" rows="3" style="padding-left: 40px; resize: vertical;"></textarea>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="documents">Upload Supporting Documents (Optional)</label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-file-upload"></i>
                <input type="file" id="documents" name="documents[]" multiple class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="padding-left: 40px; padding-top: 7px;">
            </div>
            <small class="text-muted" style="display:block; margin-top:5px; font-size:12px;">You can select multiple files. Accepted formats: PDF, JPG, PNG (Max 5MB each)</small>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 30px; font-size: 16px; padding: 12px;">
            <i class="fa-solid fa-paper-plane"></i> Submit Application
        </button>
    </form>
</div>

<style>
.admission-form-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 40px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
}
.input-icon-wrapper {
    position: relative;
}
.input-icon-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}
.input-icon-wrapper .form-control {
    padding-left: 42px;
}
.btn-primary {
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}
.btn-primary:hover {
    background: var(--primary-dark);
}
.btn-block {
    width: 100%;
    display: block;
}
.form-group {
    margin-bottom: 20px;
}
.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-main);
}
.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-main);
    color: var(--text-main);
    box-sizing: border-box;
}
.form-control:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.auth-links a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}
.auth-links a:hover {
    text-decoration: underline;
}
</style>
