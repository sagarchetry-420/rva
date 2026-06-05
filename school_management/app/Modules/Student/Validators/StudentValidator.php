<?php
namespace App\Modules\Student\Validators;

/**
 * StudentValidator — Validates student form data
 */
class StudentValidator
{
    private array $errors = [];

    /**
     * Validate student data for add/edit
     * @param array    $data           Form data
     * @param int|null $exceptStudentId Student ID to exclude from uniqueness checks (for edits)
     * @param int|null $exceptUserId   User ID to exclude from email uniqueness
     */
    public function validate(array $data, ?int $exceptStudentId = null, ?int $exceptUserId = null): bool
    {
        $this->errors = [];

        // First Name — required, alpha + spaces, max 50
        $firstName = trim($data['first_name'] ?? '');
        if (empty($firstName)) {
            $this->errors['first_name'] = 'First name is required.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
            $this->errors['first_name'] = 'First name must contain only letters and spaces.';
        } elseif (strlen($firstName) > 50) {
            $this->errors['first_name'] = 'First name must not exceed 50 characters.';
        }

        // Last Name — required, alpha + spaces, max 50
        $lastName = trim($data['last_name'] ?? '');
        if (empty($lastName)) {
            $this->errors['last_name'] = 'Last name is required.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
            $this->errors['last_name'] = 'Last name must contain only letters and spaces.';
        } elseif (strlen($lastName) > 50) {
            $this->errors['last_name'] = 'Last name must not exceed 50 characters.';
        }

        // Class ID — required, must exist
        $classId = $data['class_id'] ?? '';
        if (empty($classId)) {
            $this->errors['class_id'] = 'Class is required.';
        } elseif (!filter_var($classId, FILTER_VALIDATE_INT) || $classId <= 0) {
            $this->errors['class_id'] = 'Invalid class selected.';
        } else {
            $db = \Database::getInstance();
            if (!$db->fetch("SELECT 1 FROM classes WHERE class_id = ?", [(int)$classId])) {
                $this->errors['class_id'] = 'Selected class does not exist.';
            }
        }

        // Gender — required, must be valid
        $gender = $data['gender'] ?? '';
        if (empty($gender)) {
            $this->errors['gender'] = 'Gender is required.';
        } elseif (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
            $this->errors['gender'] = 'Gender must be Male, Female, or Other.';
        }

        // Email — REQUIRED (credentials sent via email)
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $this->errors['email'] = 'Email is required (login credentials are sent via email).';
        } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            $this->errors['email'] = 'Please enter a valid email address.';
        } else {
            // Check uniqueness in users table
            $db = \Database::getInstance();
            $sql = "SELECT 1 FROM users WHERE email = ?";
            $params = [$email];
            if ($exceptUserId) {
                $sql .= " AND user_id != ?";
                $params[] = $exceptUserId;
            }
            if ($db->fetch($sql, $params)) {
                $this->errors['email'] = 'This email address is already registered.';
            }
        }

        // Phone — optional, but if provided must be 10 digits
        $phone = trim($data['phone'] ?? '');
        if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            $this->errors['phone'] = 'Phone number must be exactly 10 digits.';
        }

        // Parent Phone — optional, 10 digits
        $parentPhone = trim($data['parent_phone'] ?? '');
        if (!empty($parentPhone) && !preg_match('/^[0-9]{10}$/', $parentPhone)) {
            $this->errors['parent_phone'] = 'Parent phone must be exactly 10 digits.';
        }

        // Date of Birth — optional, valid date, not future
        $dob = trim($data['date_of_birth'] ?? '');
        if (!empty($dob)) {
            $date = \DateTime::createFromFormat('!Y-m-d', $dob);
            if (!$date || $date->format('Y-m-d') !== $dob) {
                $this->errors['date_of_birth'] = 'Invalid date format.';
            } elseif ($dob > date('Y-m-d')) {
                $this->errors['date_of_birth'] = 'Date of birth cannot be in the future.';
            }
        }

        // Roll Number — check for duplicate in same class
        $rollNumber = trim($data['roll_number'] ?? '');
        if (!empty($rollNumber) && !empty($classId) && !isset($this->errors['class_id'])) {
            $repo = new \App\Modules\Student\Repositories\StudentRepository();
            if ($repo->rollNumberExists($rollNumber, (int)$classId, $exceptStudentId)) {
                $this->errors['roll_number'] = "Roll number '{$rollNumber}' is already assigned in this class.";
            }
        }

        // Parent Name — optional, alpha + spaces
        $parentName = trim($data['parent_name'] ?? '');
        if (!empty($parentName) && !preg_match('/^[a-zA-Z\s.]+$/', $parentName)) {
            $this->errors['parent_name'] = 'Parent name must contain only letters, spaces, and dots.';
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return reset($this->errors) ?: '';
    }

    public function errorString(): string
    {
        return implode(' | ', $this->errors);
    }
}
