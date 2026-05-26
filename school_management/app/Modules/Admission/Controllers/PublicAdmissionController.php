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
                'settings'  => $settings
            ], 'auth');
            return;
        }

        // Get classes for the dropdown
        $classes = $this->db->fetchAll("SELECT * FROM classes ORDER BY class_name, section");

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

            if (empty($firstName) || empty($lastName) || empty($classId) || empty($phone) || empty($dob) || empty($gender) || empty($parentName) || empty($parentPhone)) {
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

            // Prevent duplicate submissions
            $duplicateCheck = $this->db->fetch("SELECT * FROM student_applications WHERE phone = ?", [$phone]);
            if ($duplicateCheck) {
                $this->flash('error', 'An application with this phone number has already been submitted.');
                $this->redirect(moduleUrl('public', 'admission'));
            }

            if (!empty($email)) {
                $emailCheck = $this->db->fetch("SELECT * FROM student_applications WHERE email = ?", [$email]);
                if ($emailCheck) {
                    $this->flash('error', 'An application with this email address has already been submitted.');
                    $this->redirect(moduleUrl('public', 'admission'));
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
                        $fileName = time() . '_' . $i . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['documents']['name'][$i]));
                        $targetFile = $uploadDir . $fileName;
                        
                        if ($_FILES['documents']['size'][$i] > 5 * 1024 * 1024) {
                            $this->flash('error', 'One of the documents exceeds the 5MB limit.');
                            $this->redirect(moduleUrl('public', 'admission'));
                        }
                        
                        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                            $this->flash('error', 'Invalid document format detected. Only PDF, JPG, and PNG are allowed.');
                            $this->redirect(moduleUrl('public', 'admission'));
                        }

                        if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $targetFile)) {
                            $documentPaths[] = 'uploads/applications/' . $fileName;
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
            'settings'  => $settings
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
}
