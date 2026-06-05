<?php
namespace App\Modules\Admission\Controllers;

/**
 * PublicAdmissionController — Handle public student application submissions
 */
class PublicAdmissionController extends \Controller
{
    public function index(): void
    {
        // Get admission settings
        $settingsRaw = $this->db->fetchAll("SELECT setting_name, setting_value FROM admission_settings");
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        
        // Handle mapping from legacy DB seed names
        if (isset($settings['admission_form_open']) && !isset($settings['is_open'])) {
            $settings['is_open'] = ($settings['admission_form_open'] === 'yes' || $settings['admission_form_open'] == 1) ? 1 : 0;
        }
        if (isset($settings['application_deadline']) && !isset($settings['deadline'])) {
            $settings['deadline'] = $settings['application_deadline'];
        }
        if (!isset($settings['max_applications'])) {
            $settings['max_applications'] = 100;
        }

        $isOpen = !empty($settings['is_open']);
        $deadlinePassed = !empty($settings['deadline']) && strtotime($settings['deadline'] . ' 23:59:59') < time();
        
        if (!$isOpen || $deadlinePassed) {
            $this->render('Modules/Admission/Views/public_admission_closed', [
                'pageTitle' => 'Admissions Closed',
                'settings'  => $settings,
                'moduleCss' => ['Admission/public_admission_closed.css']
            ], 'auth');
            return;
        }

        // Get classes for the dropdown (Option 1: Hide sections, group by class name)
        $allClasses = $this->db->fetchAll("SELECT * FROM classes ORDER BY LENGTH(class_name), class_name, section");
        $classes = [];
        $seenClassNames = [];
        
        foreach ($allClasses as $cls) {
            if (!in_array($cls['class_name'], $seenClassNames)) {
                $classes[] = $cls;
                $seenClassNames[] = $cls['class_name'];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            
            // Check if max applications reached
            $count = $this->db->fetch("SELECT COUNT(*) as cnt FROM student_applications WHERE status = 'pending'");
            if ($settings && $settings['max_applications'] > 0 && $count['cnt'] >= $settings['max_applications']) {
                $this->flash('error', 'We apologize, but the maximum number of applications has been reached.');
                $this->redirect(moduleUrl('public', 'admission'));
            }

            $firstName   = trim($this->input('first_name', ''));
            $lastName    = trim($this->input('last_name', ''));
            $classId     = (int)$this->input('class_id', 0);
            $phone       = trim($this->input('phone', ''));
            $email       = trim($this->input('email', ''));
            $dob         = trim($this->input('date_of_birth', ''));
            $gender      = trim($this->input('gender', ''));
            $parentName  = trim($this->input('parent_name', ''));
            $parentPhone = trim($this->input('parent_phone', ''));
            $address     = trim($this->input('address', ''));

            if (empty($firstName) || empty($lastName) || empty($classId) || empty($phone) || empty($email) || empty($dob) || empty($gender) || empty($parentName) || empty($parentPhone)) {
                $this->flash('error', 'Please fill in all required fields.');
                $this->redirect(moduleUrl('public', 'admission'));
            }
            
            if (!preg_match('/^[0-9]{10}$/', $phone) || !preg_match('/^[0-9]{10}$/', $parentPhone)) {
                $this->flash('error', 'Please enter exactly a 10 digit phone number.');
                $this->redirect(moduleUrl('public', 'admission'));
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->flash('error', 'Please enter a valid email address.');
                $this->redirect(moduleUrl('public', 'admission'));
            }

            // Prevent duplicate submissions by email across both applications and existing users
            if (!empty($email)) {
                $emailCheckApp = $this->db->fetch("SELECT * FROM student_applications WHERE TRIM(LOWER(email)) = TRIM(LOWER(?))", [$email]);
                $emailCheckUser = $this->db->fetch("SELECT * FROM users WHERE TRIM(LOWER(email)) = TRIM(LOWER(?))", [$email]);
                
                if ($emailCheckApp || $emailCheckUser) {
                    $this->flash('error', 'An application or account with this email address has already been registered.');
                    $this->redirect(moduleUrl('public', 'admission'));
                    return;
                }
            }
            
            $documentPaths = [];
            if (isset($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
                $uploadDir = APP_ROOT . '/uploads/applications/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileCount = count($_FILES['documents']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                        if ($_FILES['documents']['size'][$i] > 5 * 1024 * 1024) {
                            $this->flash('error', 'One of the documents exceeds the 5MB limit.');
                            $this->redirect(moduleUrl('public', 'admission'));
                        }
                        
                        $originalName = $_FILES['documents']['name'][$i];
                        $tmpName = $_FILES['documents']['tmp_name'][$i];
                        
                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                        
                        // Strict MIME checking to detect fake extensions
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmpName);
                        finfo_close($finfo);
                        
                        $validMimes = ['application/pdf', 'image/jpeg', 'image/png'];
                        
                        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']) || !in_array($mime, $validMimes)) {
                            // Immediately delete the temp file if malicious
                            @unlink($tmpName);
                            $this->flash('error', 'Security Error: Invalid or malicious document format detected.');
                            $this->redirect(moduleUrl('public', 'admission'));
                        }
                        
                        // Generate a completely safe, random filename (prevent double extension attacks like shell.php.jpg)
                        $safeFileName = md5(time() . uniqid() . $i) . '.' . $ext;
                        $targetFile = $uploadDir . $safeFileName;

                        if (move_uploaded_file($tmpName, $targetFile)) {
                            $documentPaths[] = 'uploads/applications/' . $safeFileName;
                        }
                    }
                }
            }
            $encodedDocuments = !empty($documentPaths) ? json_encode($documentPaths) : null;

            $data = [
                'student_name'    => trim($firstName . ' ' . $lastName),
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'date_of_birth'   => $dob,
                'gender'          => $gender,
                'class_id'        => $classId,
                'phone'           => $phone,
                'email'           => $email,
                'parent_name'     => $parentName,
                'parent_phone'    => $parentPhone,
                'address'         => $address,
                'status'          => 'pending',
                'documents'       => $encodedDocuments,
                'created_at'      => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->insert('student_applications', $data);
                $appId = $this->db->getConnection()->lastInsertId();
                $this->redirect('/public/admission_success?id=' . $appId);
            } catch (\Exception $e) {
                // If it fails (maybe a column is missing), log it and show a generic error
                error_log("Admission Submission Error: " . $e->getMessage());
                $this->flash('error', 'There was a technical problem submitting your application. Please try again later.');
                $this->redirect(moduleUrl('public', 'admission'));
            }
        }

        $this->render('Modules/Admission/Views/public_admission', [
            'pageTitle' => 'Student Admission Form',
            'classes'   => $classes,
            'settings'  => $settings,
            'moduleCss' => ['Admission/public_admission.css']
        ], 'auth');
    }

    public function success(): void
    {
        $appId = (int)$this->input('id', 0);
        $application = $this->db->fetch("SELECT a.*, c.class_name, c.section FROM student_applications a LEFT JOIN classes c ON a.class_id = c.class_id WHERE a.id = ?", [$appId]);

        if (!$application) {
            $this->redirect(moduleUrl('public', 'admission'));
            return;
        }

        $this->render('Modules/Admission/Views/admission_success', [
            'pageTitle' => 'Application Submitted',
            'application' => $application
        ], 'auth');
    }
    
    public function downloadReceipt(): void
    {
        $appId = (int)$this->input('id', 0);
        $application = $this->db->fetch("SELECT a.*, c.class_name, c.section FROM student_applications a LEFT JOIN classes c ON a.class_id = c.class_id WHERE a.id = ?", [$appId]);

        if (!$application) {
            $this->redirect(moduleUrl('public', 'admission'));
            return;
        }

        $this->render('Modules/Admission/Views/application_receipt', [
            'pageTitle' => 'Application Receipt',
            'application' => $application
        ]);
    }
    public function track(): void
    {
        $application = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $appIdInput = trim($this->input('app_id', ''));
            $phone = trim($this->input('phone', ''));
            $dob = trim($this->input('dob', ''));

            if (empty($appIdInput) || empty($phone) || empty($dob)) {
                $error = 'Please provide Application ID, Phone Number, and Date of Birth.';
            } else {
                // Extract numeric ID if APP- prefix is used
                $numericId = preg_replace('/[^0-9]/', '', $appIdInput);
                if (empty($numericId)) {
                    $error = 'Invalid Application ID format.';
                } else {
                    $numericId = (int)$numericId;
                    $application = $this->db->fetch(
                        "SELECT a.*, c.class_name, c.section 
                         FROM student_applications a 
                         LEFT JOIN classes c ON a.class_id = c.class_id 
                         WHERE a.id = ? AND a.phone = ? AND a.date_of_birth = ?",
                        [$numericId, $phone, $dob]
                    );

                    if (!$application) {
                        $error = 'No application found with the provided details.';
                    }
                }
            }
        }

        $this->render('Modules/Admission/Views/track_application', [
            'pageTitle' => 'Track Application Status',
            'application' => $application,
            'error' => $error,
            'moduleCss' => ['Admission/track_application.css']
        ], 'auth');
    }
}
