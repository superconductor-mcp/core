<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ResourceCall
{
    /**
     * @param string $uri The URI the resource is listed and read by.
     * @param string|null $name A name for the resource; the kebab-case class name when omitted.
     */
    public function __construct(
        public string $uri,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $mimeType = null,
        public ?string $title = null,
        /** The resource's size in bytes, if known. */
        public ?int $size = null,
    ) {}
}
