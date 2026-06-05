<?php
namespace App\Modules\Teacher\Services;

use App\Modules\Teacher\Repositories\TeacherRepository;
use App\Modules\Auth\Repositories\UserRepository;
use App\Modules\Auth\Services\AuthMailService;
use Database;

/**
 * TeacherService — Business logic for teacher management
 */
class TeacherService
{
    private TeacherRepository $teacherRepo;
    private UserRepository $userRepo;
    private AuthMailService $mailService;

    public function __construct()
    {
        $this->teacherRepo = new TeacherRepository();
        $this->userRepo = new UserRepository();
        $this->mailService = new AuthMailService();
    }

    /**
     * Create a new teacher and corresponding user account
     */
    public function createTeacher(array $data): array
    {
        $db = Database::getInstance();

        return $db->transaction(function() use ($data) {
            // Generate credentials
            $username = $this->generateUsername($data['first_name'], $data['last_name']);
            $plainPassword = $this->generateRandomPassword();
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            // 1. Create User
            $userData = [
                'username'  => $username,
                'email'     => $data['email'],
                'password_hash'  => $hashedPassword,
                'user_type' => 'teacher',
                'is_active' => 1
            ];
            $userId = $this->userRepo->create($userData);

            // 2. Create Teacher
            $teacherData = [
                'user_id'                => $userId,
                'first_name'             => $data['first_name'],
                'last_name'              => $data['last_name'],
                'date_of_birth'          => $data['date_of_birth'] ?: null,
                'gender'                 => $data['gender'],
                'phone'                  => $data['phone'] ?: null,
                'email'                  => $data['email'],
                'qualification'          => $data['qualification'] ?: null,
                'subject_specialization' => $data['subject_specialization'] ?: null,
                'joining_date'           => $data['joining_date'] ?: date('Y-m-d')
            ];
            $this->teacherRepo->create($teacherData);

            // 3. Send Credentials via Email
            $emailSent = $this->mailService->sendWelcomeEmail(
                $data['email'],
                $data['first_name'] . ' ' . $data['last_name'],
                $username,
                $plainPassword,
                'Teacher Portal'
            );

            $message = 'Teacher registered successfully.';
            if (!$emailSent) {
                $message .= ' However, the credential email failed to send.';
            }

            return ['success' => true, 'message' => $message];
        });
    }

    /**
     * Update an existing teacher
     */
    public function updateTeacher(int $teacherId, array $data): array
    {
        $teacher = $this->teacherRepo->findById($teacherId);
        if (!$teacher) {
            return ['success' => false, 'message' => 'Teacher not found.'];
        }

        $db = Database::getInstance();

        return $db->transaction(function() use ($teacher, $teacherId, $data) {
            // Update User table if email changed
            if ($teacher['email'] !== $data['email']) {
                $this->userRepo->update($teacher['user_id'], ['email' => $data['email']]);
            }

            $teacherData = [
                'first_name'             => $data['first_name'],
                'last_name'              => $data['last_name'],
                'date_of_birth'          => $data['date_of_birth'] ?: null,
                'gender'                 => $data['gender'],
                'phone'                  => $data['phone'] ?: null,
                'email'                  => $data['email'],
                'qualification'          => $data['qualification'] ?: null,
                'subject_specialization' => $data['subject_specialization'] ?: null,
                'joining_date'           => $teacher['joining_date']
            ];

            $updated = $this->teacherRepo->update($teacherId, $teacherData);

            if (!$updated) {
                return ['success' => true, 'message' => 'No changes made.', 'no_change' => true];
            }

            return ['success' => true, 'message' => 'Teacher updated successfully.'];
        });
    }

    /**
     * Delete a teacher
     */
    public function deleteTeacher(int $teacherId): array
    {
        $teacher = $this->teacherRepo->findById($teacherId);
        if (!$teacher) {
            return ['success' => false, 'message' => 'Teacher not found.'];
        }

        // Deleting the user will cascade delete the teacher due to FOREIGN KEY ON DELETE CASCADE
        $deleted = $this->userRepo->delete($teacher['user_id']);

        if ($deleted) {
            return ['success' => true, 'message' => 'Teacher deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to delete teacher.'];
    }

    /**
     * Export teachers to CSV
     */
    public function exportCsv(?string $status = null, string $search = ''): void
    {
        $teachers = $this->teacherRepo->getAll($status, $search);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=teachers_list_' . date('Ymd') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Name', 'Username', 'Email', 'Phone', 'Gender', 'Qualification', 'Specialization', 'Joining Date']);

        foreach ($teachers as $t) {
            fputcsv($output, [
                $t['first_name'] . ' ' . $t['last_name'],
                $t['username'] ?? 'N/A',
                $t['email'],
                $t['phone'] ?? 'N/A',
                $t['gender'],
                $t['qualification'] ?? 'N/A',
                $t['subject_specialization'] ?? 'N/A',
                $t['joining_date'] ?? 'N/A'
            ]);
        }
        fclose($output);
        exit;
    }

    public function exportPdf(?string $status = null, string $search = ''): void
    {
        $teachers = $this->teacherRepo->getAll($status, $search);

        require_once APP_ROOT . '/includes/fpdf/fpdf.php';
        $pdf = new \FPDF('L', 'mm', 'A4'); // Landscape for tables
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, APP_NAME . ' - Teachers List', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(0, 7, 'Generated: ' . date('d M Y'), 0, 1, 'C');
        $pdf->Ln(5);

        // Table Header
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(41, 128, 185);
        $pdf->SetTextColor(255);
        $pdf->Cell(50, 8, 'Name', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Gender', 1, 0, 'L', true);
        $pdf->Cell(65, 8, 'Email', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Phone', 1, 0, 'L', true);
        $pdf->Cell(65, 8, 'Specialization', 1, 0, 'L', true);
        $pdf->Cell(35, 8, 'Joining Date', 1, 1, 'L', true);

        // Table Data
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0);
        $fill = false;

        foreach ($teachers as $t) {
            $name = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));

            if ($fill) {
                $pdf->SetFillColor(245, 245, 245);
            }

            $pdf->Cell(50, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($name, 0, 25)), 1, 0, 'L', $fill);
            $pdf->Cell(25, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($t['gender'] ?? '', 0, 10)), 1, 0, 'L', $fill);
            $pdf->Cell(65, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($t['email'] ?? '', 0, 35)), 1, 0, 'L', $fill);
            $pdf->Cell(35, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($t['phone'] ?? 'N/A', 0, 15)), 1, 0, 'L', $fill);
            $pdf->Cell(65, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($t['subject_specialization'] ?? 'N/A', 0, 30)), 1, 0, 'L', $fill);
            $pdf->Cell(35, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', substr($t['joining_date'] ?? 'N/A', 0, 15)), 1, 1, 'L', $fill);
            
            $fill = !$fill;
        }

        $pdf->Output('D', 'teachers_list_' . date('Ymd') . '.pdf');
        exit;
    }

    public function deactivateTeacher(int $teacherId, string $leavingDate, string $leavingReason): array
    {
        $teacher = $this->teacherRepo->findById($teacherId);
        if (!$teacher) {
            return ['success' => false, 'message' => 'Teacher not found.'];
        }

        $db = \Database::getInstance();

        $db->transaction(function() use ($teacherId, $leavingDate, $leavingReason, $teacher, $db) {
            $db->update('teachers', [
                'status' => 'Inactive',
                'leaving_date' => $leavingDate,
                'leaving_reason' => $leavingReason
            ], 'teacher_id = ?', [$teacherId]);

            // If leaving date is in the past, disable login immediately
            if (!empty($teacher['user_id']) && strtotime($leavingDate) < strtotime(date('Y-m-d'))) {
                $db->update('users', ['is_active' => 0], 'user_id = ?', [$teacher['user_id']]);
            }
        });

        return ['success' => true, 'message' => 'Teacher deactivated. Notice period handled.'];
    }

    public function reactivateTeacher(int $teacherId): array
    {
        $teacher = $this->teacherRepo->findById($teacherId);
        if (!$teacher) {
            return ['success' => false, 'message' => 'Teacher not found.'];
        }

        $db = \Database::getInstance();

        $db->transaction(function() use ($teacherId, $teacher, $db) {
            $db->update('teachers', [
                'status' => 'Active',
                'leaving_date' => null,
                'leaving_reason' => null
            ], 'teacher_id = ?', [$teacherId]);

            if (!empty($teacher['user_id'])) {
                $db->update('users', ['is_active' => 1], 'user_id = ?', [$teacher['user_id']]);
            }
        });

        return ['success' => true, 'message' => 'Teacher reactivated successfully.'];
    }

    /**
     * Generate a unique username like T-FirstnameLastname
     */
    private function generateUsername(string $firstName, string $lastName): string
    {
        $base = 'T-' . preg_replace('/[^a-zA-Z0-9]/', '', $firstName . $lastName);
        $base = strtolower($base);
        
        $username = $base;
        $counter = 1;

        while ($this->userRepo->usernameExists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate a secure random password (8 chars)
     */
    private function generateRandomPassword(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }
}
