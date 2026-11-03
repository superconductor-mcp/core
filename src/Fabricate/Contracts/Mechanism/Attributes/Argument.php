<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

/**
 * Describes a tool's or prompt's constructor parameter: in the tool's input schema, or as the prompt argument's
 * description and title. A tool argument of type int, string or bool can name a header: from 2026-07-28 its schema
 * carries it as x-mcp-header, and a Streamable HTTP call mirrors the argument into Mcp-Param-{header}.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Argument
{
    public function __construct(
        public ?string $description = null,
        public ?string $title = null,
        public ?string $header = null,
    ) {}
}
