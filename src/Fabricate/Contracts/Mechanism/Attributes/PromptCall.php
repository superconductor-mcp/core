<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class PromptCall
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $title = null,
    ) {}
}
