<?php

namespace Fabricate\Client;

use Closure;
use InvalidArgumentException;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Contracts\IOPools\IOPoolsException;
use Voyager\Contracts\IOPools\Promise;
use Voyager\NutsAndBolts\MultipleInstanceManager;

// the application's MCP connections, one client per entry in mcp.mcp_servers, built by the transport the entry names
class ClientManager extends MultipleInstanceManager
{
    protected string $driver_key = 'type';

    protected ?string $default = null;

    public function getDefaultInstance(): string
    {
        return $this->default
            ?? array_key_first($this->definitions())
            ?? throw new InvalidArgumentException('No MCP servers are defined in mcp.mcp_servers.');
    }

    public function setDefaultInstance(string $name): void
    {
        $this->default = $name;
    }

    public function getInstanceConfig(string $name): array
    {
        $config = $this->definitions()[$name] ?? null;

        if (! is_array($config)) {
            throw new InvalidArgumentException("MCP server [{$name}] is not defined in mcp.mcp_servers.");
        }

        // a Claude Desktop entry names no type: a command means stdio
        if (! isset($config['type']) && isset($config['command'])) {
            $config['type'] = 'stdio';
        }

        return ['name' => $name, ...$config];
    }

    /** @return array<string, array> */
    public function definitions(): array
    {
        return (array) $this->config->get('mcp.mcp_servers', []);
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->definitions());
    }

    public function client(?string $name = null): MCPClient
    {
        return $this->instance($name);
    }

    // every defined server, each connecting at once; read each client's ready() or status() for how it went
    /** @return array<string, MCPClient> */
    public function connectAll(): array
    {
        $clients = [];

        foreach ($this->names() as $name) {
            $clients[$name] = $this->client($name);
        }

        return $clients;
    }

    // every client's tools under one roof, each renamed {server}__{tool} so two servers can share a tool name
    /** @return array<string, array> */
    public function tools(): array
    {
        $tools = [];

        foreach ($this->instances as $name => $client) {
            foreach ($client->tools() as $tool) {
                $tools["{$name}__{$tool['name']}"] = $tool;
            }
        }

        return $tools;
    }

    // a call by the merged name goes to the client that owns the tool
    public function callTool(string $prefixed_name, array $arguments = []): Promise
    {
        [$server, $tool] = explode('__', $prefixed_name, 2) + [1 => null];

        if (is_null($tool) || ! isset($this->instances[$server])) {
            throw new InvalidArgumentException("[{$prefixed_name}] is not a {server}__{tool} name of a connected server.");
        }

        return $this->instances[$server]->callTool($tool, $arguments);
    }

    // every connection starts closing in the same frame; this returns once each one has fully ended
    public function closeAll(): void
    {
        [$clients, $this->instances] = [$this->instances, []];

        foreach ($clients as $client) {
            $client->close();
        }

        $open = array_filter($clients, fn (MCPClient $client): bool => ! $client->closed());

        if ($open === []) {
            return;
        }

        try {
            // works inside a running loop and after it stopped: until() borrows the loop either way
            $this->app->make(Loop::class)->until(
                fn (): bool => array_all($open, fn (MCPClient $client): bool => $client->closed())
            );
        } catch (IOPoolsException $e) {
            // the loop was stopped or ran dry before every server exited; they were already signalled
            app('log')->warning('mcp.clients closeAll ended early: '.$e->getMessage());
        }
    }

    // a resolved client is opened at once, unless its entry is disabled
    /** @var Closure(ElicitRequest, MCPClient): ElicitAnswer|null */
    protected ?Closure $elicit = null;

    // every client this manager builds from here on answers elicitation/create with this handler
    public function onElicit(Closure $handler): static
    {
        $this->elicit = $handler;

        return $this;
    }

    protected function resolve(string $name): mixed
    {
        $client = parent::resolve($name);

        if (! is_null($this->elicit)) {
            $client->onElicit($this->elicit);
        }

        if ($this->getInstanceConfig($name)['disabled'] ?? false) {
            $client->disable();
        } else {
            $client->open();
        }

        return $client;
    }
}
