<?php
namespace App\Modules\Teacher\Validators;

use App\Modules\Teacher\Repositories\TeacherRepository;
use App\Modules\Auth\Repositories\UserRepository;

/**
 * TeacherValidator — Input validation for Teacher creation and updates
 */
class TeacherValidator
{
    private TeacherRepository $teacherRepo;
    private UserRepository $userRepo;
    private array $errors = [];

    public function __construct()
    {
        $this->teacherRepo = new TeacherRepository();
        $this->userRepo = new UserRepository();
    }

    /**
     * Validate incoming data for Teacher
     */
    public function validate(array $data, ?int $exceptTeacherId = null, ?int $exceptUserId = null): bool
    {
        $this->errors = [];

        // 1. First Name
        $firstName = trim($data['first_name'] ?? '');
        if (empty($firstName)) {
            $this->errors['first_name'] = 'First name is required.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $firstName)) {
            $this->errors['first_name'] = 'First name must contain only letters and spaces.';
        } elseif (strlen($firstName) > 50) {
            $this->errors['first_name'] = 'First name must not exceed 50 characters.';
        }

        // 2. Last Name
        $lastName = trim($data['last_name'] ?? '');
        if (empty($lastName)) {
            $this->errors['last_name'] = 'Last name is required.';
        } elseif (!preg_match('/^[a-zA-Z\s]+$/', $lastName)) {
            $this->errors['last_name'] = 'Last name must contain only letters and spaces.';
        } elseif (strlen($lastName) > 50) {
            $this->errors['last_name'] = 'Last name must not exceed 50 characters.';
        }

        // 3. Email Address
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $this->errors['email'] = 'Email is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            $this->errors['email'] = 'Please enter a valid email address.';
        } else {
            if ($this->userRepo->emailExists($email, $exceptUserId)) {
                $this->errors['email'] = 'This email is already registered in the system.';
            }
            if ($this->teacherRepo->teacherEmailExists($email, $exceptTeacherId)) {
                $this->errors['email'] = 'This email is already linked to another teacher profile.';
            }
        }

        // 4. Gender
        $allowedGenders = ['Male', 'Female', 'Other'];
        $gender = trim($data['gender'] ?? '');
        if (empty($gender)) {
            $this->errors['gender'] = 'Gender is required.';
        } elseif (!in_array($gender, $allowedGenders, true)) {
            $this->errors['gender'] = 'Please select a valid gender.';
        }

        // 5. Date of Birth
        $dob = trim($data['date_of_birth'] ?? '');
        if (!empty($dob)) {
            $date = \DateTime::createFromFormat('!Y-m-d', $dob);
            if (!$date || $date->format('Y-m-d') !== $dob) {
                $this->errors['date_of_birth'] = 'Invalid Date of Birth format.';
            } elseif ($dob > date('Y-m-d')) {
                $this->errors['date_of_birth'] = 'Date of birth cannot be in the future.';
            }
        }

        // 6. Joining Date
        $joiningDate = trim($data['joining_date'] ?? '');
        if ($exceptTeacherId !== null) {
            // For updates, the joining date is locked and cannot be modified
            $originalTeacher = $this->teacherRepo->findById($exceptTeacherId);
            $originalDate = $originalTeacher['joining_date'] ?? '';
            if ($joiningDate !== $originalDate) {
                $this->errors['joining_date'] = 'Joining Date is locked and cannot be modified.';
            }
        } else {
            // For creation, validate that it is not in the past
            if (!empty($joiningDate)) {
                $date = \DateTime::createFromFormat('Y-m-d', $joiningDate);
                if (!$date || $date->format('Y-m-d') !== $joiningDate) {
                    $this->errors['joining_date'] = 'Invalid Joining Date format.';
                } else {
                    $today = new \DateTime('today');
                    $today->setTime(0, 0, 0);
                    $joining = clone $date;
                    $joining->setTime(0, 0, 0);
                    if ($joining < $today) {
                        $this->errors['joining_date'] = 'Joining Date cannot be in the past.';
                    }
                }
            }
        }

        // 7. Phone
        $phone = trim($data['phone'] ?? '');
        if (!empty($phone)) {
            if (!preg_match('/^[0-9]{10}$/', $phone)) {
                $this->errors['phone'] = 'Phone number must be exactly 10 digits.';
            }
        }

        // 8. Qualification
        $qualification = trim($data['qualification'] ?? '');
        if (!empty($qualification)) {
            if (!preg_match('/^[a-zA-Z0-9\s\.,\-&]+$/', $qualification)) {
                $this->errors['qualification'] = 'Qualification contains invalid characters.';
            } elseif (strlen($qualification) > 150) {
                $this->errors['qualification'] = 'Qualification must not exceed 150 characters.';
            }
        }

        // 9. Subject Specialization
        $specialization = trim($data['subject_specialization'] ?? '');
        if (!empty($specialization)) {
            if (!preg_match('/^[a-zA-Z0-9\s\.,\-&]+$/', $specialization)) {
                $this->errors['subject_specialization'] = 'Subject Specialization contains invalid characters.';
            } elseif (strlen($specialization) > 150) {
                $this->errors['subject_specialization'] = 'Subject Specialization must not exceed 150 characters.';
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate deactivation request data
     */
    public function validateDeactivate(array $data): bool
    {
        $this->errors = [];

        // 1. Teacher ID
        $teacherId = (int)($data['teacher_id'] ?? 0);
        if (!$teacherId) {
            $this->errors['teacher_id'] = 'Invalid teacher ID.';
        } else {
            $teacher = $this->teacherRepo->findById($teacherId);
            if (!$teacher) {
                $this->errors['teacher_id'] = 'Teacher not found.';
            }
        }

        // 2. Leaving Date
        $leavingDate = trim($data['leaving_date'] ?? '');
        if (empty($leavingDate)) {
            $this->errors['leaving_date'] = 'Leaving Date is required.';
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $leavingDate);
            if (!$date || $date->format('Y-m-d') !== $leavingDate) {
                $this->errors['leaving_date'] = 'Invalid Leaving Date format.';
            } else {
                $today = new \DateTime('today');
                $today->setTime(0, 0, 0);
                $leaving = clone $date;
                $leaving->setTime(0, 0, 0);
                if ($leaving < $today) {
                    $this->errors['leaving_date'] = 'Leaving Date cannot be in the past.';
                }
            }
        }

        // 3. Leaving Reason
        $leavingReason = trim($data['leaving_reason'] ?? '');
        $allowedReasons = ['Resigned', 'Contract Ended', 'Terminated', 'Other'];
        if (empty($leavingReason)) {
            $this->errors['leaving_reason'] = 'Leaving Reason is required.';
        } elseif (!in_array($leavingReason, $allowedReasons, true)) {
            $this->errors['leaving_reason'] = 'Please select a valid leaving reason.';
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
