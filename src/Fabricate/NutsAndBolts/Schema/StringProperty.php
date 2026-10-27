<?php

namespace Fabricate\NutsAndBolts\Schema;

use DateTimeImmutable;
use InvalidArgumentException;
use Fabricate\Contracts\NutsAndBolts\SchemaProperty;

class StringProperty implements SchemaProperty
{
    public const array FORMATS = ['email', 'uri', 'date', 'date-time'];

    public function __construct(
        public ?string $description = null,
        public ?string $title = null,
        public ?int $min_length = null,
        public ?int $max_length = null,
        public ?string $format = null,
    ) {
        if (($min_length ?? 0) < 0 || ($max_length ?? 0) < 0) {
            throw new InvalidArgumentException('min_length and max_length must not be negative.');
        }

        if (! is_null($format) && ! in_array($format, self::FORMATS, true)) {
            throw new InvalidArgumentException('format must be one of: '.implode(', ', self::FORMATS).'.');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => 'string',
            'title' => $this->title,
            'description' => $this->description,
            'minLength' => $this->min_length,
            'maxLength' => $this->max_length,
            'format' => $this->format,
        ], fn (mixed $value): bool => ! is_null($value));
    }

    public function problem(mixed $value): ?string
    {
        if (! is_string($value)) {
            return 'must be a string.';
        }

        $length = mb_strlen($value);

        return match (true) {
            ! is_null($this->min_length) && $length < $this->min_length => "must be at least {$this->min_length} characters.",
            ! is_null($this->max_length) && $length > $this->max_length => "must be at most {$this->max_length} characters.",
            $this->format === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false => 'must be an email address.',
            $this->format === 'uri' && ! preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $value) => 'must be a URI.',
            $this->format === 'date' && ! self::isDate($value) => 'must be a date (YYYY-MM-DD).',
            // RFC 3339: date, T, time with optional fraction, then Z or an offset
            $this->format === 'date-time' && ! (preg_match('/^\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}:\d{2}(\.\d+)?([Zz]|[+-]\d{2}:\d{2})$/', $value) && self::isDate(substr($value, 0, 10))) => 'must be a date-time (RFC 3339).',
            default => null,
        };
    }

    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
