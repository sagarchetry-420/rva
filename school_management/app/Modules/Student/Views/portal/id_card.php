<?php
/**
 * Student ID Card View
 * Variables: $student, $academic, $session
 */
// Extract data safely
$name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
$dob = date('d M Y', strtotime($student['date_of_birth']));
$phone = htmlspecialchars($student['phone'] ?? 'N/A');
$address = htmlspecialchars(substr($student['address'] ?? 'N/A', 0, 50));
$parentName = htmlspecialchars($student['parent_name'] ?? 'N/A');
$classStr = $academic ? htmlspecialchars($academic['class_name'] . ' ' . $academic['section']) : 'Not Assigned';
$rollNo = htmlspecialchars($academic['roll_number'] ?? $student['roll_number'] ?? 'N/A');

// Handle photo
$photo = baseUrl('assets/images/default-avatar.png'); // Default fallback
if (!empty($student['photo'])) {
    // Check if it's already a full URL or needs baseUrl
    if (strpos($student['photo'], 'http') === 0) {
        $photo = $student['photo'];
    } else {
        $photo = baseUrl($student['photo']);
    }
}
?>

<div class="page-header no-print">
    <div>
        <h1><i class="fas fa-address-card"></i> Student Identity Card</h1>
        <p>Download or print your official school ID card.</p>
    </div>
    <div class="header-actions">
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print / Download PDF</button>
    </div>
</div>

<div class="id-card-container">
    <div class="id-card">
        <!-- Front of ID Card -->
        <div class="id-card-front">
            <div class="id-header">
                <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <img src="<?php echo asset('img/logo.png'); ?>" alt="School Logo" style="height: 35px; width: 35px; border-radius: 50%; object-fit: cover;">
                    <div style="text-align: left;">
                        <h2>RVA SCHOOL</h2>
                        <p>Knowledge is Power</p>
                    </div>
                </div>
            </div>
            <div class="id-body">
                <div class="id-photo-container">
                    <img src="<?php echo $photo; ?>" alt="Student Photo" class="id-photo">
                </div>
                <div class="id-details">
                    <h3 class="student-name"><?php echo $name; ?></h3>
                    <div class="detail-row">
                        <span class="label">Class:</span>
                        <span class="value"><?php echo $classStr; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Roll No:</span>
                        <span class="value"><?php echo $rollNo; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">D.O.B:</span>
                        <span class="value"><?php echo $dob; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Session:</span>
                        <span class="value">
                            <?php 
                                $sessName = $academic['session_name'] ?? $session['session_name'] ?? '';
                                echo htmlspecialchars($sessName !== '' ? $sessName : 'Not Assigned'); 
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="id-footer">
                <div class="signature">
                    <div class="sig-line">Principal Signature</div>
                </div>
            </div>
        </div>
        
        <!-- Back of ID Card -->
        <div class="id-card-back">
            <div class="id-header-back">
                <h3>Terms & Conditions</h3>
            </div>
            <div class="id-back-content">
                <ul>
                    <li>This card is the property of RVA School.</li>
                    <li>It must be carried at all times while in the school premises.</li>
                    <li>If found, please return to the school administration office.</li>
                </ul>
                <div class="emergency-contact">
                    <strong>Emergency Contact:</strong><br>
                    Parent: <?php echo $parentName; ?><br>
                    Phone: <?php echo $phone; ?>
                </div>
                <div class="school-address">
                    <strong>RVA School Address:</strong><br>
                    123 Education Lane, Knowledge City<br>
                    Phone: (555) 123-4567
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ID Card Styles */
.id-card-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    background: #f3f4f6;
    border-radius: 8px;
    margin-top: 20px;
}

.id-card {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.id-card-front, .id-card-back {
    width: 250px;
    height: 380px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    overflow: hidden;
    position: relative;
    border: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
}

/* Front Design */
.id-header {
    background: linear-gradient(135deg, var(--primary-color, #2c5f2d) 0%, #1a3b1b 100%);
    color: white;
    text-align: center;
    padding: 15px 10px;
}

.id-header h2 {
    margin: 0;
    font-size: 1.2rem;
    letter-spacing: 1px;
    color: #ffffff !important;
}

.id-header p {
    margin: 2px 0 0 0;
    font-size: 0.7rem;
    opacity: 0.9;
    color: #ffffff !important;
}

.id-body {
    padding: 15px;
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.id-photo-container {
    width: 90px;
    height: 110px;
    border: 3px solid var(--primary-color, #2c5f2d);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 15px;
    background: #f9fafb;
}

.id-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.id-details {
    width: 100%;
}

.student-name {
    margin: 0 0 10px 0;
    text-align: center;
    font-size: 1.1rem;
    color: #1f2937;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 5px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 0.8rem;
}

.detail-row .label {
    font-weight: 600;
    color: #6b7280;
}

.detail-row .value {
    color: #111827;
    font-weight: 500;
}

.id-footer {
    padding: 10px 15px;
    text-align: right;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
}

.signature {
    display: inline-block;
    text-align: center;
    min-width: 100px;
    min-height: 25px;
}

.sig-line {
    font-size: 0.65rem;
    color: #6b7280;
    border-top: 1px solid #9ca3af;
    padding-top: 2px;
    margin-top: 15px;
}

/* Back Design */
.id-header-back {
    background: #4b5563;
    color: white;
    text-align: center;
    padding: 10px;
}

.id-header-back h3 {
    margin: 0;
    font-size: 0.9rem;
    color: #ffffff !important;
}

.id-back-content {
    padding: 15px;
    font-size: 0.75rem;
    color: #374151;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.id-back-content ul {
    margin: 0;
    padding-left: 15px;
}

.id-back-content li {
    margin-bottom: 5px;
}

.emergency-contact, .school-address {
    background: #f3f4f6;
    padding: 8px;
    border-radius: 4px;
    border-left: 3px solid var(--primary-color, #2c5f2d);
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    .id-card, .id-card * {
        visibility: visible;
    }
    
    .id-card {
        position: absolute;
        left: 50%;
        top: 20px;
        transform: translateX(-50%);
        gap: 10px;
    }
    
    .no-print {
        display: none !important;
    }
    
    .id-card-container {
        padding: 0;
        background: transparent;
    }
    
    .id-card-front, .id-card-back {
        box-shadow: none;
        border: 1px solid #000;
    }
    
    /* Ensure background colors print */
    .id-header, .id-header-back, .id-photo-container {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>
