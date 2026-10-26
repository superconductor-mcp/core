<?php

namespace Fabricate\NutsAndBolts\Attributes;

use Attribute;

#[Attribute(\Attribute::TARGET_CLASS)]
class Server
{
    public function __construct(
        public readonly string $name
    ) {}
}