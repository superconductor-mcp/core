<?php

declare(strict_types=1);

namespace Fabricate\Server;

use Fabricate\Contracts\Server\MCPServer;
use Fabricate\NutsAndBolts\Attributes\Server as ServerAttribute;
use InvalidArgumentException;
use ReflectionClass;
use Voyager\Contracts\Vessel\TheServiceContainer;
use Voyager\NutsAndBolts\DataObjects\Str;

class ServerRepository
{
    public const string BINDING_PREFIX = 'mcp.server.';

    /** @var array<string, class-string<MCPServer>> */
    protected array $servers = [];

    public function __construct(protected readonly TheServiceContainer $container) {}

    /** @param class-string<MCPServer> $class */
    public function register(string $class): void
    {
        if (! is_subclass_of($class, MCPServer::class) || (new ReflectionClass($class))->isAbstract()) {
            throw new InvalidArgumentException("[{$class}] is not a concrete MCP server.");
        }

        $name = static::nameFor($class);

        if (isset($this->servers[$name]) && $this->servers[$name] !== $class) {
            throw new InvalidArgumentException(
                "MCP server [{$name}] is already registered by [{$this->servers[$name]}]; [{$class}] needs another name."
            );
        }

        $this->servers[$name] = $class;
        $this->container->bind(self::BINDING_PREFIX.$name, $class);
    }

    public function has(string $name): bool
    {
        return isset($this->servers[$name]);
    }

    /** @return array<string, class-string<MCPServer>> */
    public function all(): array
    {
        return $this->servers;
    }

    public function get(string $name): MCPServer
    {
        if (! $this->has($name)) {
            $known = $this->servers === []
                ? 'no servers are registered'
                : 'registered servers: '.implode(', ', array_keys($this->servers));

            throw new InvalidArgumentException("Unknown MCP server [{$name}]; {$known}.");
        }

        return $this->container->make(self::BINDING_PREFIX.$name);
    }

    /** @param class-string<MCPServer> $class */
    public static function nameFor(string $class): string
    {
        $attributes = (new ReflectionClass($class))->getAttributes(ServerAttribute::class);

        return $attributes === []
            ? Str::kebab(class_basename($class))
            : $attributes[0]->newInstance()->name;
    }
}
