<?php

namespace Fabricate\NutsAndBolts\Attributes;

use Attribute;

// marks a class whose handle(string $value) suggests values for one argument
#[Attribute(\Attribute::TARGET_CLASS)]
class Completes
{

}
