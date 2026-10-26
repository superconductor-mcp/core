<?php

namespace Fabricate\NutsAndBolts;

use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// suggestions for one argument, most relevant first; the spec caps one answer at 100 values
final class CompletionResult implements Arrayable
{
    public const int MAX_VALUES = 100;

    /** @var list<string> */
    public readonly array $values;

    public readonly bool $has_more;

    /**
     * @param list<string> $values suggestions ranked by relevance; anything past 100 is cut and has_more is set
     * @param int|null $total how many matches exist in all, when the completer knows
     */
    public function __construct(
        array $values = [],
        public readonly ?int $total = null,
        bool $has_more = false,
    ) {
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new InvalidArgumentException('Completion values must be strings.');
            }
        }

        $values = array_values($values);
        $this->values = array_slice($values, 0, self::MAX_VALUES);
        $this->has_more = $has_more || count($values) > self::MAX_VALUES;
    }

    public function toArray(): array
    {
        return array_filter([
            'values' => $this->values,
            'total' => $this->total,
            'hasMore' => $this->has_more ?: null,
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
