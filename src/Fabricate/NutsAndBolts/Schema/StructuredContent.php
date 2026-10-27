<?php

namespace Fabricate\NutsAndBolts\Schema;

use InvalidArgumentException;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

/**
 * 2025-06-18: what a tool returns when its result is data, not prose. The data goes out as `structuredContent`;
 * `$content` is the unstructured side, and when left null the data goes there too as one JSON text item,
 * which the spec asks for so clients that predate structured output still see the result.
 * A tool that declares an outputSchema promises its data matches it; nothing here checks that.
 */
final readonly class StructuredContent
{
    /**
     * @param array<string, mixed> $data a JSON object
     * @param list<SchemaPrimitive>|null $content
     */
    public function __construct(
        public array $data,
        public ?array $content = null,
    ) {
        if ($data !== [] && array_is_list($data)) {
            throw new InvalidArgumentException('StructuredContent data must be a JSON object, not a list.');
        }

        if (! is_null($content) && (! array_is_list($content) || $content !== array_filter($content, fn (mixed $item): bool => $item instanceof SchemaPrimitive))) {
            throw new InvalidArgumentException('StructuredContent content must be a list of schema primitives.');
        }
    }

    /** @return list<SchemaPrimitive> */
    public function content(): array
    {
        return $this->content ?? [new TextProperty(json_encode((object) $this->data))];
    }
}
