<?php
/**
 * ============================================================
 * Validator — Reusable Validation Engine
 * ============================================================
 * Usage:
 *   $v = new Validator($_POST, [
 *       'first_name' => 'required|alpha|max:50',
 *       'email'      => 'required|email|unique:users,email',
 *       'phone'      => 'phone',
 *   ]);
 *   if ($v->fails()) { $errors = $v->errors(); }
 */

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages;

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            $label = ucwords(str_replace(['_', '-'], ' ', $field));

            foreach ($rules as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }
                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $label, $params);
                }
                if (isset($this->errors[$field])) break;
            }
        }
    }

    public function fails(): bool { return !empty($this->errors); }
    public function passes(): bool { return empty($this->errors); }
    public function errors(): array { return $this->errors; }

    public function firstError(): string
    {
        foreach ($this->errors as $e) {
            return is_array($e) ? $e[0] : $e;
        }
        return '';
    }

    public function errorString(string $sep = ' | '): string
    {
        $flat = [];
        foreach ($this->errors as $e) {
            $flat = array_merge($flat, (array)$e);
        }
        return implode($sep, $flat);
    }

    private function addError(string $field, string $rule, string $msg): void
    {
        $this->errors[$field][] = $this->customMessages["{$field}.{$rule}"] ?? $msg;
    }

    // ─── Rules ───

    private function validateRequired(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '' || (is_array($v) && empty($v)))
            $this->addError($f, 'required', "{$l} is required.");
    }

    private function validateEmail(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $v))
            $this->addError($f, 'email', "{$l} must be a valid email address.");
    }

    private function validateAlpha(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!preg_match('/^[a-zA-Z\s]+$/', $v))
            $this->addError($f, 'alpha', "{$l} must contain only letters and spaces.");
    }

    private function validateAlphaNum(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!preg_match('/^[a-zA-Z0-9\s]+$/', $v))
            $this->addError($f, 'alphaNum', "{$l} must contain only letters, numbers, and spaces.");
    }

    private function validateInteger(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!filter_var($v, FILTER_VALIDATE_INT))
            $this->addError($f, 'integer', "{$l} must be a whole number.");
    }

    private function validateNumeric(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!is_numeric($v))
            $this->addError($f, 'numeric', "{$l} must be a number.");
    }

    private function validateMin(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $min = $p[0] ?? 0;
        if (is_numeric($v)) {
            if ((float)$v < (float)$min) $this->addError($f, 'min', "{$l} must be at least {$min}.");
        } else {
            if (strlen($v) < (int)$min) $this->addError($f, 'min', "{$l} must be at least {$min} characters.");
        }
    }

    private function validateMax(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $max = $p[0] ?? PHP_INT_MAX;
        if (is_numeric($v)) {
            if ((float)$v > (float)$max) $this->addError($f, 'max', "{$l} must not exceed {$max}.");
        } else {
            if (strlen($v) > (int)$max) $this->addError($f, 'max', "{$l} must not exceed {$max} characters.");
        }
    }

    private function validateIn(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!in_array($v, $p, true))
            $this->addError($f, 'in', "{$l} must be one of: " . implode(', ', $p) . ".");
    }

    private function validateDate(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $fmt = $p[0] ?? 'Y-m-d';
        $d = DateTime::createFromFormat($fmt, $v);
        if (!$d || $d->format($fmt) !== $v)
            $this->addError($f, 'date', "{$l} must be a valid date.");
    }

    private function validateDateNotFuture(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if ($d && $d > new DateTime('today'))
            $this->addError($f, 'dateNotFuture', "{$l} cannot be a future date.");
    }

    private function validateDateFuture(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if ($d && $d < new DateTime('today'))
            $this->addError($f, 'dateFuture', "{$l} must be today or a future date.");
    }

    private function validatePhone(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        if (!preg_match('/^[0-9]{10}$/', $v))
            $this->addError($f, 'phone', "{$l} must be exactly 10 digits.");
    }

    private function validateUnique(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $table = $p[0] ?? '';
        $col = $p[1] ?? $f;
        $exceptId = $p[2] ?? null;
        $idCol = $p[3] ?? 'id';
        if (!$table) return;

        $db = Database::getInstance();
        $sql = "SELECT 1 FROM {$table} WHERE {$col} = ?";
        $bindings = [$v];
        if ($exceptId) { $sql .= " AND {$idCol} != ?"; $bindings[] = $exceptId; }
        $sql .= " LIMIT 1";

        if ($db->fetch($sql, $bindings))
            $this->addError($f, 'unique', "{$l} is already taken.");
    }

    private function validateExists(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $table = $p[0] ?? '';
        $col = $p[1] ?? $f;
        if (!$table) return;

        $db = Database::getInstance();
        if (!$db->fetch("SELECT 1 FROM {$table} WHERE {$col} = ? LIMIT 1", [$v]))
            $this->addError($f, 'exists', "{$l} does not exist.");
    }

    private function validateConfirmed(string $f, $v, string $l, array $p): void
    {
        if ($v === null || $v === '') return;
        $cf = $p[0] ?? $f . '_confirmation';
        if ($v !== ($this->data[$cf] ?? null))
            $this->addError($f, 'confirmed', "{$l} confirmation does not match.");
    }
}
