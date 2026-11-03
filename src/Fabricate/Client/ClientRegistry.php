<?php

namespace Fabricate\Client;

use Fabricate\Mechanism\EntityRegistry;
use Fabricate\Contracts\Mechanism\Attributes\ClientConfig;

class ClientRegistry extends EntityRegistry
{
    protected static string $attribute = ClientConfig::class;

    protected array $clients = [];

    protected string $subclass = ClientConfiguration::class;

    protected string $subclass_label = "MCP Client";

    protected string $binding_prefix = 'mcp.client.';

    protected function entity(): array
    {
        return $this->clients;
    }

    protected function setEntity(string $name, string $class): void
    {
        $this->clients[$name] = $class;
    }
}
