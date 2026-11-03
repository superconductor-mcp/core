<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::TARGET_FUNCTION)]
class RequiresCapability
{
    /**
     * @param string $capability A dotted wire path, such as tools or resources.subscribe.
     */
    public function __construct(
        public string $capability,
    ) {}
}
