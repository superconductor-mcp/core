<?php

namespace Fabricate\Server;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\ResourceTemplateCall;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\EntityRegistry;
use Fabricate\Mechanism\ResourceTemplate;

/**
 * The resource templates, keyed by URI template.
 */
class ResourceTemplateRegistry extends EntityRegistry
{
    protected static string $attribute = ResourceTemplateCall::class;

    protected array $templates = [];

    protected string $subclass = ResourceTemplate::class;

    protected string $subclass_label = "Resource Template";

    protected string $binding_prefix = 'mcp.resource-template.';

    /**
     * @param class-string<ResourceTemplate> $class
     */
    public static function nameFor(string $class): string
    {
        return $class::uriTemplate();
    }

    /**
     * @return class-string<ResourceTemplate>
     */
    public function template(string $uri_template): string
    {
        if(! $this->has($uri_template)) {
            throw new SuperconductorMCPException("Unknown resource template [{$uri_template}].", RPCErrorCode::INTERNAL_ERROR->value);
        }

        return $this->templates[$uri_template];
    }

    protected function entity(): array
    {
        return $this->templates;
    }

    protected function setEntity(string $name, string $class): void
    {
        $this->templates[$name] = $class;
    }
}
