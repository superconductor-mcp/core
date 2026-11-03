<?php

namespace Fabricate\Mechanism;

use InvalidArgumentException;

/**
 * The suggestions a prompt offers for one argument. The spec caps a response at 100 values, so any past that are cut and has_more is set.
 */
final readonly class CompletionResult
{
    public const int MAX_VALUES = 100;

    /** @var list<string> */
    public array $values;

    public bool $has_more;

    /**
     * @param list<string> $values
     * @param int|null $total How many matches exist in all, when the prompt knows.
     */
    public function __construct(
        array $values = [],
        public ?int $total = null,
        bool $has_more = false,
    ) {
        foreach($values as $value)
        {
            if(! is_string($value)) throw new InvalidArgumentException('Completion values must be strings.');
        }

        $values = array_values($values);
        $this->values = array_slice($values, 0, self::MAX_VALUES);
        $this->has_more = $has_more || count($values) > self::MAX_VALUES;
    }
}
