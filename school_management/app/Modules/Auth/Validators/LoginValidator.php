<?php
namespace App\Modules\Auth\Validators;

/**
 * LoginValidator — Validates login form input
 */
class LoginValidator
{
    private array $errors = [];

    public function validate(array $data): bool
    {
        $this->errors = [];

        // Email/Username
        $identifier = trim($data['email'] ?? '');
        if (empty($identifier)) {
            $this->errors['email'] = 'Email or username is required.';
        }

        // Password
        $password = $data['password'] ?? '';
        if (empty($password)) {
            $this->errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 4) {
            $this->errors['password'] = 'Password must be at least 4 characters.';
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
}
