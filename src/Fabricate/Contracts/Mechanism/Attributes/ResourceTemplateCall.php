<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ResourceTemplateCall
{
    /**
     * @param string $uriTemplate The RFC 6570 URI template the resources it reads fit, such as file:///{path}.
     * @param string|null $name A name for the template; the kebab-case class name when omitted.
     */
    public function __construct(
        public string $uriTemplate,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?string $title = null,
    ) {}
}
