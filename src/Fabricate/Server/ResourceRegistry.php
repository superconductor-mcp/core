<?php

namespace Fabricate\Server;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\ResourceCall;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\EntityRegistry;
use Fabricate\Mechanism\Resource;

/**
 * The resources at fixed URIs, keyed by URI.
 */
class ResourceRegistry extends EntityRegistry
{
    protected static string $attribute = ResourceCall::class;

    protected array $resources = [];

    protected string $subclass = Resource::class;

    protected string $subclass_label = "Resource";

    protected string $binding_prefix = 'mcp.resource.';

    /**
     * @param class-string<Resource> $class
     */
    public static function nameFor(string $class): string
    {
        return $class::uri();
    }

    /**
     * @return class-string<Resource>
     */
    public function resource(string $uri): string
    {
        if(! $this->has($uri)) {
            throw new SuperconductorMCPException("Unknown resource [{$uri}].", RPCErrorCode::INTERNAL_ERROR->value);
        }

        return $this->resources[$uri];
    }

    protected function entity(): array
    {
        return $this->resources;
    }

    protected function setEntity(string $name, string $class): void
    {
        $this->resources[$name] = $class;
    }
}
