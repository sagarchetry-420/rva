<?php
namespace App\Modules\Student\Services;

use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuthMailService;

/**
 * StudentService — Business logic for student management
 */
class StudentService
{
    private StudentRepository $studentRepo;
    private UserRepository $userRepo;
    private \Database $db;

    public function __construct()
    {
        $this->studentRepo = new StudentRepository();
        $this->userRepo = new UserRepository();
        $this->db = \Database::getInstance();
    }

    /**
     * Create a new student with user account and send credentials email
     */
    public function createStudent(array $data): array
    {
        $this->db->beginTransaction();

        try {
            // Generate username
            $username = $this->generateUsername($data['first_name'], $data['last_name']);

            // Generate random password
            $plainPassword = $this->generatePassword();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            // Create user account
            $userId = $this->userRepo->create([
                'username'  => $username,
                'password_hash'  => $hashedPassword,
                'user_type' => 'student',
                'email'     => $data['email'],
            ]);

            // Generate roll number if not provided
            $rollNumber = !empty($data['roll_number'])
                ? $data['roll_number']
                : $this->generateRollNumber((int)$data['class_id']);

            // Create student record
            $studentId = $this->studentRepo->create([
                'user_id'        => $userId,
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'date_of_birth'  => $data['date_of_birth'] ?: null,
                'gender'         => $data['gender'],
                'address'        => $data['address'] ?? null,
                'phone'          => $data['phone'] ?: null,
                'email'          => $data['email'],
                'parent_name'    => $data['parent_name'] ?: null,
                'parent_phone'   => $data['parent_phone'] ?: null,
                'current_class_id'=> (int)$data['class_id'],
                'roll_number'    => $rollNumber,
                'admission_date' => date('Y-m-d'),
            ]);

            // Add student to active session in student_academics
            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            
            if ($session) {
                // Update current_session_id on student
                $this->studentRepo->update($studentId, ['current_session_id' => $session['session_id']]);
                
                $this->db->insert('student_academics', [
                    'student_id'       => $studentId,
                    'session_id'       => $session['session_id'],
                    'class_id'         => (int)$data['class_id'],
                    'roll_number'      => $rollNumber,
                    'admission_status' => 'Active',
                    'promotion_status' => 'Pending'
                ]);

                // Automatically generate Admission Fee invoice if > 0
                $classInfo = $this->db->fetch("SELECT admission_fee FROM classes WHERE class_id = ?", [(int)$data['class_id']]);
                if ($classInfo && $classInfo['admission_fee'] > 0) {
                    $category = $this->db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Admission Fee'");
                    $categoryId = $category ? $category['category_id'] : 1; // Fallback to 1

                    $dueDate = date('Y-m-d', strtotime('+30 days'));
                    $adminUserId = $_SESSION['user_id'] ?? 1; // Fallback to 1 if not set
                    $this->db->insert('fees', [
                        'student_id'     => $studentId,
                        'session_id'     => $session['session_id'],
                        'category_id'    => $categoryId,
                        'amount'         => $classInfo['admission_fee'],
                        'due_date'       => $dueDate,
                        'payment_status' => 'Pending',
                        'created_by'     => $adminUserId,
                    ]);
                }
            }

            $this->db->commit();

            // Send credentials email (non-blocking — failure doesn't rollback)
            $emailSent = $this->sendCredentialsEmail(
                $data['email'], $data['first_name'], $username, $plainPassword, $data['phone'] ?? ''
            );

            return [
                'success'    => true,
                'student_id' => $studentId,
                'username'   => $username,
                'password'   => $plainPassword,
                'email_sent' => $emailSent,
                'message'    => 'Student registered successfully!' . ($emailSent ? ' Credentials sent via email.' : ''),
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to register student: ' . $e->getMessage()];
        }
    }

    /**
     * Update an existing student
     */
    public function updateStudent(int $studentId, array $data): array
    {
        $current = $this->studentRepo->findById($studentId);
        if (!$current) {
            return ['success' => false, 'message' => 'Student not found.'];
        }

        // Check if anything changed
        $changed = false;
        $fields = ['first_name', 'last_name', 'class_id', 'gender', 'phone',
                    'parent_name', 'parent_phone', 'date_of_birth', 'email', 'roll_number'];
        foreach ($fields as $field) {
            $newVal = $data[$field] ?? '';
            $oldVal = $current[$field] ?? '';
            if ((string)$newVal !== (string)$oldVal) {
                $changed = true;
                break;
            }
        }

        if (!$changed) {
            return ['success' => true, 'message' => 'No changes were made.', 'no_change' => true];
        }

        $this->db->beginTransaction();

        try {
            // Update student record
            $this->studentRepo->update($studentId, [
                'first_name'    => $data['first_name'],
                'last_name'     => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?: null,
                'gender'        => $data['gender'],
                'phone'         => $data['phone'] ?: null,
                'email'         => $data['email'],
                'parent_name'   => $data['parent_name'] ?: null,
                'parent_phone'  => $data['parent_phone'] ?: null,
                'current_class_id'=> (int)$data['class_id'],
                'roll_number'   => $data['roll_number'] ?: null,
            ]);

            // Sync with student_academics if active session exists
            $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
            $session = $academicRepo->getActiveSession();
            if ($session) {
                $this->db->update('student_academics', [
                    'class_id' => (int)$data['class_id'],
                    'roll_number' => $data['roll_number'] ?: null
                ], 'student_id = ? AND session_id = ?', [$studentId, $session['session_id']]);
            }

            // Sync email in users table
            $this->userRepo->update($current['user_id'], ['email' => $data['email']]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Student details updated successfully!'];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to update student: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a student and their user account
     */
    public function deleteStudent(int $studentId): array
    {
        if ($this->studentRepo->delete($studentId)) {
            return ['success' => true, 'message' => 'Student record has been deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete student.'];
    }

    /**
     * Generate a unique roll number: YEAR-SECTION-XX
     */
    public function generateRollNumber(int $classId): ?string
    {
        $classData = $this->db->fetch(
            "SELECT section FROM classes WHERE class_id = ?",
            [$classId]
        );
        if (!$classData) return null;

        $year = date('Y');
        $section = $classData['section'] ?: 'A';
        $count = $this->studentRepo->countByClass($classId) + 1;

        $rollNumber = $year . '-' . $section . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);
        
        // Loop to avoid collisions if previous students were deleted
        while ($this->studentRepo->rollNumberExists($rollNumber, $classId)) {
            $count++;
            $rollNumber = $year . '-' . $section . '-' . str_pad($count, 2, '0', STR_PAD_LEFT);
        }

        return $rollNumber;
    }

    /**
     * Export students as CSV
     */
    public function exportCsv(?int $classId = null): void
    {
        $students = $this->studentRepo->getForExport($classId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="students_export_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, ['Roll No', 'First Name', 'Last Name', 'Username', 'Class', 'Section', 'DOB', 'Gender', 'Phone', 'Email', 'Parent Name', 'Parent Phone']);

        foreach ($students as $row) {
            fputcsv($output, [
                $row['roll_number'], $row['first_name'], $row['last_name'], $row['username'],
                $row['class_name'], $row['section'], $row['date_of_birth'], $row['gender'],
                $row['phone'], $row['email'], $row['parent_name'], $row['parent_phone'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export students as PDF
     */
    public function exportPdf(?int $classId = null): void
    {
        $students = $this->studentRepo->getForExport($classId);

        require_once APP_ROOT . '/includes/fpdf/fpdf.php';
        $pdf = new \FPDF('L', 'mm', 'A4'); // Landscape for tables
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, APP_NAME . ' - Students List', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, 'Generated: ' . date('d M Y'), 0, 1, 'C');
        $pdf->Ln(5);

        // Table Header
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(41, 128, 185);
        $pdf->SetTextColor(255);
        $pdf->Cell(30, 8, 'Roll No', 1, 0, 'L', true);
        $pdf->Cell(50, 8, 'Name', 1, 0, 'L', true);
        $pdf->Cell(40, 8, 'Class', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Gender', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Email', 1, 0, 'L', true);
        $pdf->Cell(60, 8, 'Parent', 1, 1, 'L', true);

        // Table Data
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0);
        $fill = false;

        foreach ($students as $row) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $cls = trim(($row['class_name'] ?? '') . ' ' . ($row['section'] ?? ''));

            if ($fill) {
                $pdf->SetFillColor(245, 245, 245);
            }

            $pdf->Cell(30, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($row['roll_number'] ?? '', 0, 15)), 1, 0, 'L', $fill);
            $pdf->Cell(50, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($name, 0, 25)), 1, 0, 'L', $fill);
            $pdf->Cell(40, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($cls, 0, 20)), 1, 0, 'L', $fill);
            $pdf->Cell(25, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['gender'] ?? ''), 1, 0, 'L', $fill);
            $pdf->Cell(60, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($row['email'] ?? '', 0, 30)), 1, 0, 'L', $fill);
            $pdf->Cell(60, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($row['parent_name'] ?? '', 0, 25)), 1, 1, 'L', $fill);
            
            $fill = !$fill;
        }

        $pdf->Output('D', 'students_export_' . date('Y-m-d') . '.pdf');
        exit;
    }

    /**
     * Import students from CSV
     */
    public function importCsv(array $file, int $classId, bool $sendEmails): array
    {
        // Prevent PHP from timing out when uploading 100+ students and sending emails
        set_time_limit(0);

        $db = \Database::getInstance();
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle === false) {
            return ['success' => false, 'message' => 'Could not read the uploaded CSV file.'];
        }

        // Read Warning Row (Row 1)
        $warningRow = fgetcsv($handle);
        // Read Instructions Row (Row 2)
        $instructionRow = fgetcsv($handle);
        // Read actual Header Row (Row 3)
        $header = fgetcsv($handle);
        
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'message' => 'The CSV file is empty or missing the header row.'];
        }

        // Validate template headers strictly (Dynamic Template)
        $expectedHeaders = [
            'First Name', 'Last Name', 'Gender (Male/Female/Other)', 'Date of Birth (YYYY-MM-DD)', 
            'Email', 'Phone', 'Parent Name', 'Parent Phone', 
            'Admission Fee Paid (Yes/No)', 'Admission Payment Method (Cash/Card/Online)', 'Admission Transaction ID / Remarks'
        ];
        
        $services = $db->fetchAll("SELECT service_name, fee_amount FROM services ORDER BY service_name ASC");
        if ($services) {
            foreach ($services as $service) {
                $expectedHeaders[] = 'Service: ' . $service['service_name'] . ' (Yes/No)';
            }
        }
        
        $expectedHeaders[] = 'Services Payment Method (Cash/Card/Online)';
        $expectedHeaders[] = 'Services Transaction ID / Remarks';

        $isTemplateValid = true;
        if (count($header) !== count($expectedHeaders)) {
            $isTemplateValid = false;
        } else {
            foreach ($expectedHeaders as $index => $expectedColumn) {
                if (!isset($header[$index]) || trim($header[$index]) !== $expectedColumn) {
                    $isTemplateValid = false;
                    break;
                }
            }
        }

        if (!$isTemplateValid) {
            fclose($handle);
            return ['success' => false, 'message' => 'Invalid CSV template. Please download the latest template.'];
        }

        // Dynamically parse Service columns from header
        $serviceColumns = [];
        foreach ($header as $index => $colName) {
            $colName = trim($colName);
            if (strpos($colName, 'Service: ') === 0) {
                // Extracts "Transport" from "Service: Transport (Yes/No)"
                $serviceName = str_replace(['Service: ', ' (Yes/No)'], '', $colName);
                $serviceInfo = $db->fetch("SELECT service_id, fee_amount FROM services WHERE service_name = ?", [$serviceName]);
                if ($serviceInfo) {
                    $override = $db->fetch("SELECT fee_amount FROM class_service_fees WHERE service_id = ? AND class_id = ?", [$serviceInfo['service_id'], $classId]);
                    $finalFee = $override ? (float)$override['fee_amount'] : (float)$serviceInfo['fee_amount'];
                    
                    $serviceColumns[$index] = [
                        'id' => $serviceInfo['service_id'],
                        'fee' => $finalFee
                    ];
                }
            }
        }

        $importedCount = 0;
        $failedCount = 0;
        $duplicateCount = 0;
        
        $paymentMethodColIndex = count($expectedHeaders) - 2;
        $txnIdColIndex = count($expectedHeaders) - 1;
        
        // Dynamically find or create the Service Fee category
        $serviceCat = $db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Service Fee'");
        if (!$serviceCat) {
            $db->insert('fee_categories', ['category_name' => 'Service Fee', 'description' => 'System generated service fees']);
            $serviceCatId = (int)$db->getConnection()->lastInsertId();
        } else {
            $serviceCatId = (int)$serviceCat['category_id'];
        }
        
        $db->transaction(function() use ($handle, $classId, $sendEmails, &$importedCount, &$failedCount, &$duplicateCount, $serviceColumns, $paymentMethodColIndex, $txnIdColIndex, $serviceCatId) {
            while (($data = fgetcsv($handle)) !== false) {
                // Strict Backend Sanitization to prevent Injections/XSS
                $firstName = preg_replace('/[^a-zA-Z\s]/', '', trim($data[0] ?? ''));
                $lastName = preg_replace('/[^a-zA-Z\s]/', '', trim($data[1] ?? ''));
                $gender = trim($data[2] ?? '');
                $dob = trim($data[3] ?? '') ?: null;
                $email = filter_var(trim($data[4] ?? ''), FILTER_SANITIZE_EMAIL);
                $phone = preg_replace('/[^0-9]/', '', trim($data[5] ?? ''));
                $parentName = preg_replace('/[^a-zA-Z\s.]/', '', trim($data[6] ?? '')) ?: null;
                $parentPhone = preg_replace('/[^0-9]/', '', trim($data[7] ?? ''));

                // Validate phone lengths to exactly 10 digits
                if ($phone && strlen($phone) !== 10) $phone = null;
                if ($parentPhone && strlen($parentPhone) !== 10) $parentPhone = null;

                if (empty($firstName) || empty($email) || empty($gender)) {
                    $failedCount++;
                    continue; // Skip invalid row
                }

                // Check for duplicate email
                if ($this->userRepo->emailExists($email)) {
                    $duplicateCount++;
                    continue; // Skip duplicate
                }

                $username = $this->generateUsername($firstName, $lastName);
                $plainPassword = $this->generatePassword();
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

                // 1. Create User
                $userData = [
                    'username'      => $username,
                    'email'         => $email,
                    'password_hash' => $hashedPassword,
                    'user_type'     => 'student',
                    'is_active'     => 1
                ];
                $userId = $this->userRepo->create($userData);

                // 2. Generate Roll Number
                $rollNumber = $this->generateRollNumber($classId);

                // Get active session
                $academicRepo = new \App\Modules\Academic\Repositories\ClassSubjectRepository();
                $session = $academicRepo->getActiveSession();
                $sessionId = $session ? $session['session_id'] : 1;

                // 3. Create Student
                $studentData = [
                    'user_id'            => $userId,
                    'first_name'         => $firstName,
                    'last_name'          => $lastName,
                    'date_of_birth'      => $dob,
                    'gender'             => $gender,
                    'current_class_id'   => $classId,
                    'current_session_id' => $sessionId,
                    'roll_number'        => $rollNumber,
                    'email'              => $email,
                    'phone'              => $phone,
                    'parent_name'        => $parentName,
                    'parent_phone'       => $parentPhone,
                    'admission_date'     => date('Y-m-d')
                ];
                $studentId = $this->studentRepo->create($studentData);
                
                // Add to student_academics
                if ($session) {
                    $this->db->insert('student_academics', [
                        'student_id'       => $studentId,
                        'session_id'       => $sessionId,
                        'class_id'         => $classId,
                        'roll_number'      => $rollNumber,
                        'admission_status' => 'Active',
                        'promotion_status' => 'Pending'
                    ]);
                }

                // Always generate Admission Fee invoice if class has an admission fee configured
                $classInfo = $this->db->fetch("SELECT admission_fee FROM classes WHERE class_id = ?", [(int)$classId]);
                if ($classInfo && $classInfo['admission_fee'] > 0) {
                    $category = $this->db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Admission Fee'");
                    $categoryId = $category ? $category['category_id'] : 1; 

                    $dueDate = date('Y-m-d', strtotime('+30 days'));
                    $adminUserId = $_SESSION['user_id'] ?? 1;
                    
                    $admissionPaid = strtolower(trim($data[8] ?? ''));
                    $paymentMethod = trim($data[9] ?? '');
                    $txnId = trim($data[10] ?? '');
                    
                    $paymentStatus = 'Pending';
                    $paymentDate = null;
                    $receiptNumber = null;
                    
                    // If they typed 'Yes', mark as Paid instantly. If 'No', it stays 'Pending'.
                    if (in_array($admissionPaid, ['yes', 'y', '1', 'true'])) {
                        $paymentStatus = 'Paid';
                        $paymentDate = date('Y-m-d');
                        $receiptNumber = 'RVA-' . date('Ymd') . '-' . rand(1000, 9999);
                        if (empty($paymentMethod)) {
                            $paymentMethod = 'Cash'; // Default to cash if left blank but marked as Yes
                        }
                    } else {
                        // If they said No (not paid), clear out the payment method so it's clean
                        $paymentMethod = null;
                    }

                    $this->db->insert('fees', [
                        'student_id'     => $studentId,
                        'session_id'     => $sessionId,
                        'category_id'    => $categoryId,
                        'amount'         => $classInfo['admission_fee'],
                        'due_date'       => $dueDate,
                        'payment_status' => $paymentStatus,
                        'payment_date'   => $paymentDate,
                        'payment_method' => $paymentMethod ?: null,
                        'receipt_number' => $receiptNumber,
                        'remarks'        => $txnId ?: null,
                        'created_by'     => $adminUserId,
                    ]);
                }

                // Global Service Payment details
                $globalServicePaymentMethod = trim($data[$paymentMethodColIndex] ?? '');
                $globalServiceTxnId = trim($data[$txnIdColIndex] ?? '');

                // Automatically assign dynamic services based on CSV columns and charge them
                foreach ($serviceColumns as $colIndex => $svc) {
                    $assignService = strtolower(trim($data[$colIndex] ?? ''));
                    
                    if (in_array($assignService, ['yes', 'y', '1', 'true'])) {
                        $this->db->insert('student_services', [
                            'student_id'      => $studentId,
                            'service_id'      => $svc['id'],
                            'session_id'      => $sessionId,
                            'enrollment_date' => date('Y-m-d'),
                            'is_active'       => 1
                        ]);
                        
                        // Instantly generate paid invoice for the service if there is a fee
                        if ($svc['fee'] > 0) {
                            $dueDate = date('Y-m-d', strtotime('+30 days'));
                            $adminUserId = $_SESSION['user_id'] ?? 1;
                            
                            $paymentStatus = 'Paid'; // Force paid because Yes = taken and paid
                            $paymentDate = date('Y-m-d');
                            $receiptNumber = 'RVA-' . date('Ymd') . '-' . rand(1000, 9999);
                            
                            $paymentMethod = $globalServicePaymentMethod;
                            if (empty($paymentMethod)) {
                                $paymentMethod = 'Cash'; // Default method for auto-paid service
                            }

                            $this->db->insert('fees', [
                                'student_id'     => $studentId,
                                'session_id'     => $sessionId,
                                'category_id'    => $serviceCatId,
                                'service_id'     => $svc['id'],
                                'amount'         => $svc['fee'],
                                'due_date'       => $dueDate,
                                'payment_status' => $paymentStatus,
                                'payment_date'   => $paymentDate,
                                'payment_method' => $paymentMethod,
                                'receipt_number' => $receiptNumber,
                                'remarks'        => 'Service Auto-charge. ' . ($globalServiceTxnId ?: ''),
                                'created_by'     => $adminUserId,
                            ]);
                        }
                    }
                }
                
                // 4. Send Email if requested
                if ($sendEmails) {
                    $this->sendCredentialsEmail($email, $firstName, $username, $plainPassword, $phone ?? '');
                }

                $importedCount++;
            }
        });

        fclose($handle);

        if ($importedCount > 0) {
            $msg = "Successfully imported {$importedCount} student(s).";
            if ($failedCount > 0) $msg .= " Failed rows: {$failedCount}.";
            if ($duplicateCount > 0) $msg .= " Skipped duplicates: {$duplicateCount}.";
            return ['success' => true, 'message' => $msg];
        } else {
            $msg = "No valid students could be imported.";
            if ($failedCount > 0) $msg .= " Failed rows: {$failedCount}.";
            if ($duplicateCount > 0) $msg .= " Skipped duplicates: {$duplicateCount}.";
            return ['success' => false, 'message' => $msg];
        }
    }

    // ─── Private Helpers ───
    
    public function withdrawStudent(int $studentId, string $leavingDate, string $leavingReason): array
    {
        $student = $this->studentRepo->findById($studentId);
        if (!$student) {
            return ['success' => false, 'message' => 'Student not found.'];
        }

        $this->db->transaction(function() use ($studentId, $leavingDate, $leavingReason, $student) {
            // Update student record with future or current leaving date
            $this->db->update('students', [
                'leaving_date' => $leavingDate,
                'leaving_reason' => $leavingReason
            ], 'student_id = ?', [$studentId]);

            // If the leaving date is today or in the past, instantly deactivate them
            if (strtotime($leavingDate) <= strtotime(date('Y-m-d'))) {
                // Disable login
                if (!empty($student['user_id'])) {
                    $this->db->update('users', ['is_active' => 0], 'user_id = ?', [(int)$student['user_id']]);
                }

                // Update current academic session admission_status
                if (!empty($student['current_session_id'])) {
                    $this->db->update('student_academics', [
                        'admission_status' => 'Left'
                    ], 'student_id = ? AND session_id = ?', [$studentId, $student['current_session_id']]);
                }
            }
        });

        return ['success' => true, 'message' => 'Student successfully withdrawn.'];
    }

    private function generateUsername(string $firstName, string $lastName): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z]/', '', $firstName) . '.' . preg_replace('/[^a-zA-Z]/', '', $lastName));
        $username = $base . rand(100, 999);

        while ($this->userRepo->usernameExists($username)) {
            $username = $base . rand(100, 9999);
        }

        return $username;
    }

    private function generatePassword(int $length = 10): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
        return substr(str_shuffle($chars), 0, $length);
    }

    private function sendCredentialsEmail(string $toEmail, string $firstName, string $username, string $password, string $phone = ''): bool
    {
        $mailService = new AuthMailService();
        return $mailService->sendWelcomeEmail($toEmail, $firstName, $username, $password, 'Student Portal');
    }
}
