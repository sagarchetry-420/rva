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
                'joining_date'           => $data['joining_date'] ?: null
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
    public function exportCsv(): void
    {
        $teachers = $this->teacherRepo->findAll();

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
