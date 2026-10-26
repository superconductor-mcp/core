<?php

namespace Fabricate\NutsAndBolts\Attributes;

use Attribute;

// marks a class whose handle() reads a resource; template variables arrive as named arguments
#[Attribute(\Attribute::TARGET_CLASS)]
class ResourceRead
{

}
