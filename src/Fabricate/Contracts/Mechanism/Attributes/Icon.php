<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;
use Fabricate\Contracts\Schema\IconTheme;

/**
 * An icon for a server, tool, prompt, resource or resource template, sent from 2025-11-25. Repeat it for several.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Icon
{
    /**
     * @param list<string>|null $sizes Such as "48x48", or "any" for a scalable icon.
     */
    public function __construct(
        public string $src,
        public ?string $mimeType = null,
        public ?array $sizes = null,
        public ?IconTheme $theme = null,
    ) {}
}
