<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ServerConfig
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $version = null,
        public ?string $title = null,
        public ?string $websiteUrl = null,
        /** How to use the server, sent with the initialize result. */
        public ?string $instructions = null,
    ) {}
}
