<?php
namespace App\Modules\Attendance\Validators;

/**
 * AttendanceValidator — Validates attendance inputs
 */
class AttendanceValidator
{
    private array $errors = [];

    public function validateBulkSave(array $data): bool
    {
        $this->errors = [];
        if (empty($data['class_id']) || !is_numeric($data['class_id'])) {
            $this->addError('class_id', 'Valid Class ID is required.');
        }

        if (empty($data['attendance_date'])) {
            $this->addError('attendance_date', 'Attendance date is required.');
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['attendance_date'])) {
            $this->addError('attendance_date', 'Invalid date format.');
        }

        if (empty($data['attendance']) || !is_array($data['attendance'])) {
            $this->addError('attendance', 'No attendance data provided.');
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
