<?php
/** Server-side input validation. Collects one error message per field. */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public function value(string $field): string
    {
        $v = $this->data[$field] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    public function passes(): bool { return !$this->errors; }
    public function errors(): array { return $this->errors; }

    public function fail(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') $this->fail($field, "$label is required.");
        return $this;
    }

    public function text(string $field, string $label, int $min, int $max, bool $required = true, ?string $pattern = null, string $patternMsg = ''): self
    {
        $v = $this->value($field);
        if ($v === '') {
            if ($required) $this->fail($field, "$label is required.");
            return $this;
        }
        $len = mb_strlen($v);
        if ($len < $min)      $this->fail($field, "$label must be at least $min characters.");
        elseif ($len > $max)  $this->fail($field, "$label must be at most $max characters.");
        elseif ($pattern && !preg_match($pattern, $v)) $this->fail($field, $patternMsg ?: "$label has invalid characters.");
        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        $v = $this->value($field);
        if ($v === '')                                         $this->fail($field, "$label is required.");
        elseif (mb_strlen($v) > 150)                           $this->fail($field, "$label is too long.");
        elseif (!filter_var($v, FILTER_VALIDATE_EMAIL))        $this->fail($field, "Enter a valid email address.");
        return $this;
    }

    /** Only addresses at the configured school domain are accepted (exact match, no sub-domains). */
    public function emailDomain(string $field, string $domain = ALLOWED_EMAIL_DOMAIN): self
    {
        $v = strtolower($this->value($field));
        if ($v !== '' && !isset($this->errors[$field]) && substr((string)strrchr($v, '@'), 1) !== strtolower($domain))
            $this->fail($field, "Use your school email address (name@$domain).");
        return $this;
    }

    /** Passwords are NOT trimmed: spaces are legitimate password characters. */
    public function password(string $field, string $label = 'Password'): self
    {
        $v = (string)($this->data[$field] ?? '');
        if ($v === '')                              $this->fail($field, "$label is required.");
        elseif (strlen($v) < 8)                     $this->fail($field, "$label must be at least 8 characters.");
        elseif (strlen($v) > 72)                    $this->fail($field, "$label must be at most 72 characters.");
        elseif (!preg_match('/[a-z]/', $v) || !preg_match('/[A-Z]/', $v) || !preg_match('/\d/', $v))
            $this->fail($field, "$label needs an uppercase letter, a lowercase letter and a number.");
        return $this;
    }

    public function matches(string $field, string $other, string $message): self
    {
        if (($this->data[$field] ?? '') !== ($this->data[$other] ?? '')) $this->fail($field, $message);
        return $this;
    }

    public function int(string $field, string $label, int $min, int $max): self
    {
        $v = $this->value($field);
        if ($v === '' || filter_var($v, FILTER_VALIDATE_INT) === false) $this->fail($field, "$label must be a whole number.");
        elseif ((int)$v < $min || (int)$v > $max)                       $this->fail($field, "$label must be between $min and $max.");
        return $this;
    }

    public function decimal(string $field, string $label, float $min, float $max): self
    {
        $v = $this->value($field);
        if ($v === '' || !is_numeric($v))        $this->fail($field, "$label must be a number.");
        elseif ((float)$v < $min || (float)$v > $max) $this->fail($field, "$label must be between $min and $max.");
        return $this;
    }

    public function date(string $field, string $label): self
    {
        $v = $this->value($field);
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) $this->fail($field, "$label must be a valid date.");
        return $this;
    }

    public function oneOf(string $field, string $label, array $allowed): self
    {
        if (!in_array($this->value($field), $allowed, true)) $this->fail($field, "$label is not a valid choice.");
        return $this;
    }

    /** ISBN-10 or ISBN-13 (hyphens/spaces allowed). */
    public function isbn(string $field, string $label = 'ISBN'): self
    {
        $v = strtoupper(str_replace(['-', ' '], '', $this->value($field)));
        if ($v === '') return $this->fail($field, "$label is required.");
        if (!preg_match('/^(\d{13}|\d{9}[\dX])$/', $v)) $this->fail($field, "$label must be a valid 10 or 13 digit ISBN.");
        return $this;
    }
}
