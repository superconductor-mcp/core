<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

/**
 * Hints about a tool's behaviour, sent as its annotations from 2025-03-26. Clients must treat them as untrusted.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ToolAnnotations
{
    public function __construct(
        public ?string $title = null,
        public ?bool $readOnlyHint = null,
        public ?bool $destructiveHint = null,
        public ?bool $idempotentHint = null,
        public ?bool $openWorldHint = null,
    ) {}
}
