<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// hints on a content item or resource: who it is for, how much it matters, when it last changed
final readonly class Annotations implements Arrayable
{
    /**
     * @param list<string>|null $audience 'user' and/or 'assistant'
     * @param float|null $priority 0 (least) to 1 (most important)
     * @param string|null $last_modified ISO 8601, e.g. 2025-01-12T15:00:58Z
     */
    public function __construct(
        public ?array $audience = null,
        public ?float $priority = null,
        public ?string $last_modified = null,
    ) {
        if (! is_null($audience) && array_diff($audience, ['user', 'assistant']) !== []) {
            throw new InvalidArgumentException("Annotations audience may only hold 'user' and 'assistant'.");
        }

        if (! is_null($priority) && ($priority < 0 || $priority > 1)) {
            throw new InvalidArgumentException('Annotations priority must be between 0 and 1.');
        }

        if (! is_null($last_modified) && date_create($last_modified) === false) {
            throw new InvalidArgumentException("Annotations lastModified [{$last_modified}] is not a date.");
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'audience' => is_null($this->audience) ? null : array_values($this->audience),
            'priority' => $this->priority,
            'lastModified' => $this->last_modified,
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
