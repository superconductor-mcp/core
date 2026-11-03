<?php

namespace Fabricate\Mechanism\Signals;

/**
 * Fired when a resource's contents change. Every MCPServer whose session subscribed to the URI sends notifications/resources/updated.
 */
final readonly class ResourceUpdated
{
    public function __construct(
        public string $uri,
        public ?string $session_id = null,
    ) {}
}
