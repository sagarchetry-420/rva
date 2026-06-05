<?php
namespace App\Modules\Fees\Validators;

/**
 * FeeValidator — Validation rules for Fee inputs
 */
class FeeValidator
{
    private array $errors = [];

    public function validateCollection(array $data): bool
    {
        $this->errors = [];
        if (empty($data['fee_id']) || !is_numeric($data['fee_id'])) {
            $this->addError('fee_id', 'Valid Fee ID is required.');
        }

        if (empty($data['payment_method'])) {
            $this->addError('payment_method', 'Payment method is required.');
        } elseif (!in_array($data['payment_method'], ['Cash', 'Bank Transfer', 'Online', 'Cheque', 'Other'])) {
            $this->addError('payment_method', 'Invalid payment method selected.');
        }

        return !$this->hasErrors();
    }

    public function validateFeeGeneration(array $data): bool
    {
        $this->errors = [];
        if (empty($data['student_id']) || !is_numeric($data['student_id'])) {
            $this->addError('student_id', 'Student is required.');
        }

        if (empty($data['category_id']) || !is_numeric($data['category_id'])) {
            $this->addError('category_id', 'Fee Category is required.');
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            $this->addError('amount', 'Amount must be strictly greater than 0.');
        }

        if (empty($data['due_date'])) {
            $this->addError('due_date', 'Due date is required.');
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $data['due_date']);
            if (!$date || $date->format('Y-m-d') !== $data['due_date']) {
                $this->addError('due_date', 'Invalid date format.');
            } else {
                $today = new \DateTime('today');
                $date->setTime(0, 0, 0);
                if ($date < $today) {
                    $this->addError('due_date', 'Due date cannot be in the past.');
                }
            }
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
