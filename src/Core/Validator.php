<?php
declare(strict_types=1);

namespace App\Core;

/** Server-side validation. Rules mirror the JavaScript checks in public/assets/app.js. */
final class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value, string $label): self
    {
        if (!isset($this->errors[$field]) && trim((string) $value) === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function maxLength(string $field, mixed $value, int $max, string $label): self
    {
        if (!isset($this->errors[$field]) && mb_strlen(trim((string) $value)) > $max) {
            $this->errors[$field] = "$label must be $max characters or fewer.";
        }
        return $this;
    }

    public function email(string $field, mixed $value): self
    {
        if (!isset($this->errors[$field]) && !filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Enter a valid email address.';
        }
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
