<?php

namespace Fabricate\NutsAndBolts;

use Voyager\Contracts\NutsAndBolts\Arrayable;

// 2025-03-26 tool annotations: hints about how a tool behaves. Every one is a hint, never a guarantee; a client must not trust them from an unknown server.
final readonly class ToolAnnotations implements Arrayable
{
    /**
     * @param string|null $title a human-readable name for the tool
     * @param bool|null $read_only the tool changes nothing in its environment (default false)
     * @param bool|null $destructive the tool may make destructive updates; only meaningful when not read-only (default true)
     * @param bool|null $idempotent calling again with the same arguments adds nothing; only meaningful when not read-only (default false)
     * @param bool|null $open_world the tool reaches outside its closed domain, e.g. the web (default true)
     */
    public function __construct(
        public ?string $title = null,
        public ?bool $read_only = null,
        public ?bool $destructive = null,
        public ?bool $idempotent = null,
        public ?bool $open_world = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'readOnlyHint' => $this->read_only,
            'destructiveHint' => $this->destructive,
            'idempotentHint' => $this->idempotent,
            'openWorldHint' => $this->open_world,
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
