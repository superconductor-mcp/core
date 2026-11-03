<?php

namespace Fabricate\Contracts\Mechanism\Attributes;

use Attribute;
use Fabricate\Contracts\Schema\Concerns\Role;

/**
 * Who a resource or resource template is for and how much it matters. lastModified is sent from 2025-11-25.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ResourceAnnotations
{
    /**
     * @param list<Role>|null $audience
     * @param int|float|null $priority From 0, least important, to 1, effectively required.
     * @param string|null $lastModified An ISO 8601 timestamp.
     */
    public function __construct(
        public ?array $audience = null,
        public int|float|null $priority = null,
        public ?string $lastModified = null,
    ) {}
}
