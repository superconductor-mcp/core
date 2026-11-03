<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

/**
 * Describes a tool's or prompt's constructor parameter: in the tool's input schema, or as the prompt argument's
 * description and title.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Argument
{
    public function __construct(
        public ?string $description = null,
        public ?string $title = null,
    ) {}
}
