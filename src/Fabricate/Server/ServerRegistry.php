<?php

namespace Fabricate\Server;

use Fabricate\Mechanism\EntityRegistry;
use Fabricate\Contracts\Mechanism\Attributes\ServerConfig;

class ServerRegistry extends EntityRegistry
{
    protected static string $attribute = ServerConfig::class;

    protected array $servers = [];

    protected string $subclass = ServerConfiguration::class;

    protected string $subclass_label = "MCP Server";

    protected string $binding_prefix = 'mcp.server.';

    protected function entity(): array
    {
        return $this->servers;
    }

    protected function setEntity(string $name, string $class): void
    {
        $this->servers[$name] = $class;
    }
}
