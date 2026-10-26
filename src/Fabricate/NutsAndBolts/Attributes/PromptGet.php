<?php

namespace Fabricate\NutsAndBolts\Attributes;

use Attribute;

// marks a class whose handle() builds a prompt's messages; the prompt's arguments arrive as named string arguments
#[Attribute(\Attribute::TARGET_CLASS)]
class PromptGet
{

}
