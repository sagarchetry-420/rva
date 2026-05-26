<?php
namespace App\Modules\Academic\Validators;

use App\Modules\Academic\Repositories\ClassRepository;
use App\Modules\Academic\Repositories\SubjectRepository;

/**
 * AcademicValidator — Input validation for Classes and Subjects
 */
class AcademicValidator
{
    private ClassRepository $classRepo;
    private SubjectRepository $subjectRepo;
    private array $errors = [];

    public function __construct()
    {
        $this->classRepo = new ClassRepository();
        $this->subjectRepo = new SubjectRepository();
    }

    /**
     * Validate Class creation/updates
     */
    public function validateClass(array $data, ?int $exceptClassId = null): bool
    {
        $this->errors = [];
        $className = trim($data['class_name'] ?? '');
        if (empty($className)) {
            $this->addError('class_name', 'Class Name is required.');
        } elseif (strlen($className) > 50) {
            $this->addError('class_name', 'Class Name must not exceed 50 characters.');
        }

        $section = trim($data['section'] ?? '');
        if (empty($section)) {
            $this->addError('section', 'Section is required.');
        } elseif (strlen($section) > 10) {
            $this->addError('section', 'Section must not exceed 10 characters.');
        }

        if (!$this->hasErrors()) {
            if ($this->classRepo->classExists($className, $section, $exceptClassId)) {
                $this->addError('class_name', 'A class with this name and section already exists.');
            }
        }

        return !$this->hasErrors();
    }

    /**
     * Validate Subject creation/updates
     */
    public function validateSubject(array $data, ?int $exceptSubjectId = null): bool
    {
        $this->errors = [];
        $subjectName = trim($data['subject_name'] ?? '');
        if (empty($subjectName)) {
            $this->addError('subject_name', 'Subject Name is required.');
        } elseif (strlen($subjectName) > 100) {
            $this->addError('subject_name', 'Subject Name must not exceed 100 characters.');
        }

        $subjectCode = trim($data['subject_code'] ?? '');
        if (empty($subjectCode)) {
            $this->addError('subject_code', 'Subject Code is required.');
        } elseif (strlen($subjectCode) > 20) {
            $this->addError('subject_code', 'Subject Code must not exceed 20 characters.');
        }

        if (!$this->hasErrors()) {
            if ($this->subjectRepo->nameExists($subjectName, $exceptSubjectId)) {
                $this->addError('subject_name', 'A subject with this name already exists.');
            } elseif ($this->subjectRepo->codeExists($subjectCode, $exceptSubjectId)) {
                $this->addError('subject_code', 'This subject code is already in use.');
            }
        }

        return !$this->hasErrors();
    }

    /**
     * Validate Class-Subject Assignment
     */
    public function validateAssignment(array $data): bool
    {
        $this->errors = [];
        if (empty($data['class_id']) || !is_numeric($data['class_id'])) {
            $this->addError('class_id', 'Valid Class ID is required.');
        }
        if (empty($data['subject_id']) || !is_numeric($data['subject_id'])) {
            $this->addError('subject_id', 'Valid Subject ID is required.');
        }
        return !$this->hasErrors();
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
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
