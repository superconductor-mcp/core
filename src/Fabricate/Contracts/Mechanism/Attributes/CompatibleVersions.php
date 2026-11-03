<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::TARGET_FUNCTION)]
class CompatibleVersions
{
    public readonly array $versions;

    public function __construct(
        string|array ...$versions,
    ) {
        $this->versions = array_merge(...array_map(fn(string|array $version) => (array) $version, $versions));
    }
}
