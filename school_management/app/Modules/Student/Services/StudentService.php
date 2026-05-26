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
     * Import students from CSV
     */
    public function importCsv(array $file, int $classId, bool $sendEmails): array
    {
        $db = \Database::getInstance();
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle === false) {
            return ['success' => false, 'message' => 'Could not read the uploaded CSV file.'];
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'message' => 'The CSV file is empty.'];
        }

        // Validate template headers
        $expectedHeaders = ['First Name', 'Last Name', 'Gender (Male/Female/Other)', 'Date of Birth (YYYY-MM-DD)', 'Email', 'Phone', 'Parent Name', 'Parent Phone'];
        $isTemplateValid = true;
        foreach ($expectedHeaders as $index => $expectedColumn) {
            if (!isset($header[$index]) || trim($header[$index]) !== $expectedColumn) {
                $isTemplateValid = false;
                break;
            }
        }

        if (!$isTemplateValid) {
            fclose($handle);
            return ['success' => false, 'message' => 'Invalid CSV template. Please download and use the official CSV Template.'];
        }

        $importedCount = 0;
        $failedCount = 0;
        $duplicateCount = 0;
        
        $db->transaction(function() use ($handle, $classId, $sendEmails, &$importedCount, &$failedCount, &$duplicateCount) {
            while (($data = fgetcsv($handle)) !== false) {
                // Expected format: First Name, Last Name, Gender, Date of Birth, Email, Phone, Parent Name, Parent Phone
                $firstName = trim($data[0] ?? '');
                $lastName = trim($data[1] ?? '');
                $gender = trim($data[2] ?? '');
                $dob = trim($data[3] ?? '') ?: null;
                $email = trim($data[4] ?? '');
                $phone = trim($data[5] ?? '');
                $parentName = trim($data[6] ?? '') ?: null;
                $parentPhone = trim($data[7] ?? '');

                // Sanitize phone numbers to match chk_student_phone and chk_parent_phone constraints
                if ($phone) {
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if (strlen($phone) < 7 || strlen($phone) > 15) $phone = null;
                } else {
                    $phone = null;
                }

                if ($parentPhone) {
                    $parentPhone = preg_replace('/[^0-9]/', '', $parentPhone);
                    if (strlen($parentPhone) < 7 || strlen($parentPhone) > 15) $parentPhone = null;
                } else {
                    $parentPhone = null;
                }

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

                // Automatically generate Admission Fee invoice if > 0
                $classInfo = $this->db->fetch("SELECT admission_fee FROM classes WHERE class_id = ?", [(int)$classId]);
                if ($classInfo && $classInfo['admission_fee'] > 0) {
                    $category = $this->db->fetch("SELECT category_id FROM fee_categories WHERE category_name = 'Admission Fee'");
                    $categoryId = $category ? $category['category_id'] : 1; // Fallback to 1

                    $dueDate = date('Y-m-d', strtotime('+30 days'));
                    $adminUserId = $_SESSION['user_id'] ?? 1; // Fallback to 1 if not set
                    $this->db->insert('fees', [
                        'student_id'     => $studentId,
                        'session_id'     => $sessionId,
                        'category_id'    => $categoryId,
                        'amount'         => $classInfo['admission_fee'],
                        'due_date'       => $dueDate,
                        'payment_status' => 'Pending',
                        'created_by'     => $adminUserId,
                    ]);
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
            // Update student record
            $this->db->update('students', [
                'leaving_date' => $leavingDate,
                'leaving_reason' => $leavingReason
            ], 'student_id = ?', [$studentId]);

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
