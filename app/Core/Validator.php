<?php
namespace App\Core;

class Validator
{
    private array $errors = [];

    public function required(string $field, $value, string $label = null): self
    {
        if ($value === null || trim((string)$value) === '') {
            $this->errors[$field] = ($label ?? $field) . ' es obligatorio.';
        }
        return $this;
    }

    public function email(string $field, $value): self
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Formato de correo inválido.';
        }
        return $this;
    }

    public function min(string $field, $value, int $len): self
    {
        if ($value && mb_strlen((string)$value) < $len) {
            $this->errors[$field] = "Debe tener al menos {$len} caracteres.";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}