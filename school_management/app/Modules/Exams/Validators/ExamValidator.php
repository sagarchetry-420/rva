<?php
namespace App\Modules\Exams\Validators;

/**
 * ExamValidator — Validates exam and schedule inputs
 */
class ExamValidator
{
    private array $errors = [];

    public function validateExam(array $data): bool
    {
        $this->errors = [];
        
        $examName = trim($data['exam_name'] ?? '');
        if (empty($examName)) {
            $this->addError('exam_name', 'Exam Name is required.');
        } elseif (strlen($examName) < 3 || strlen($examName) > 150) {
            $this->addError('exam_name', 'Exam Name must be between 3 and 150 characters.');
        }
        
        if (empty($data['start_date']) || empty($data['end_date'])) {
            $this->addError('dates', 'Start Date and End Date are required.');
        } elseif ($data['start_date'] > $data['end_date']) {
            $this->addError('dates', 'End Date must be after Start Date.');
        }

        if (empty($data['class_ids']) || !is_array($data['class_ids'])) {
            $this->addError('class_ids', 'At least one class must be assigned to the exam.');
        }

        return !$this->hasErrors();
    }

    public function validateSchedule(array $data): bool
    {
        $this->errors = [];
        if (empty($data['subject_id'])) {
            $this->addError('subject_id', 'Subject is required.');
        }
        if (empty($data['exam_date'])) {
            $this->addError('exam_date', 'Exam Date is required.');
        }
        if (empty($data['start_time']) || empty($data['end_time'])) {
            $this->addError('times', 'Start Time and End Time are required.');
        } elseif ($data['start_time'] >= $data['end_time']) {
            $this->addError('times', 'End Time must be after Start Time.');
        }

        $full = (float)($data['full_marks'] ?? 0);
        $pass = (float)($data['pass_marks'] ?? 0);
        if ($full <= 0) {
            $this->addError('full_marks', 'Full Marks must be greater than 0.');
        }
        if ($pass > $full) {
            $this->addError('pass_marks', 'Pass Marks cannot exceed Full Marks.');
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
