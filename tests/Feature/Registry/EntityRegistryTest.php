<?php

use App\Mcp\Clients\CuriousClient;
use App\Mcp\Servers\EchoServer;
use Fabricate\Client\ClientRegistry;
use Fabricate\Client\DiscoverClients;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Server\DiscoverServers;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerRegistry;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

beforeEach(function () {
    ControlPanel::setInstance($this->panel = new ControlPanel());
    $this->panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
});

afterEach(fn() => ControlPanel::setInstance(null));

it('registers a server configuration under its ServerConfig name and builds it.', function () {
    $servers = new ServerRegistry($this->panel);
    $servers->register(EchoServer::class);

    expect($servers->all())->toBe(['echo' => EchoServer::class])
        ->and($servers->get('echo'))->toBeInstanceOf(EchoServer::class);
});

it('falls back to the kebab-case class name without a ClientConfig attribute.', function () {
    $clients = new ClientRegistry($this->panel);
    $clients->register(CuriousClient::class);

    expect($clients->all())->toBe(['curious-client' => CuriousClient::class])
        ->and($clients->get('curious-client'))->toBeInstanceOf(CuriousClient::class);
});

it('refuses a class that is not a configuration of its own role.', function (string $registry, string $class) {
    expect(fn () => new $registry($this->panel)->register($class))->toThrow(InvalidArgumentException::class);
})->with([
    'client into servers' => [ServerRegistry::class, CuriousClient::class],
    'server into clients' => [ClientRegistry::class, EchoServer::class],
    'entity, not configuration' => [ServerRegistry::class, MCPServer::class],
]);

it('reports an unknown name with the names it does know.', function () {
    $servers = new ServerRegistry($this->panel);
    $servers->register(EchoServer::class);

    expect(fn () => $servers->get('nope'))->toThrow(InvalidArgumentException::class, 'registered MCP Servers: echo');
});

it('discovers the concrete configurations in the application folders.', function () {
    $app = realpath(__DIR__.'/../../../../../app');

    expect(DiscoverServers::within($app.'/Mcp/Servers', 'App\\Mcp\\Servers'))->toBe([EchoServer::class])
        ->and(DiscoverClients::within($app.'/Mcp/Clients', 'App\\Mcp\\Clients'))->toBe([CuriousClient::class])
        ->and(DiscoverServers::within($app.'/Mcp/Clients', 'App\\Mcp\\Clients'))->toBe([])
        ->and(DiscoverServers::within($app.'/Mcp/Nowhere', 'App\\Mcp\\Nowhere'))->toBe([]);
});

it('announces itself with the Implementation shape of each version.', function (string $version, array $expected) {
    $server = $this->panel->make(EchoServer::class);

    expect($server->name())->toBe('echo')
        ->and($server->implementation($version)->toArray())->toBe($expected);
})->with([
    ['2024-11-05', ['name' => 'echo', 'version' => '0.11.0']],
    ['2025-03-26', ['name' => 'echo', 'version' => '0.11.0']],
    ['2025-06-18', ['name' => 'echo', 'version' => '0.11.0']],
    ['2025-11-25', ['name' => 'echo', 'version' => '0.11.0', 'description' => 'Stop repeating yourself. .flesruoy gnitaeper potS']],
    ['2026-07-28', ['name' => 'echo', 'version' => '0.11.0', 'description' => 'Stop repeating yourself. .flesruoy gnitaeper potS']],
]);

it('cannot announce itself without a version in its attribute.', function () {
    $client = new #[\Fabricate\Contracts\Mechanism\Attributes\ClientConfig('versionless')] class(new \Fabricate\Client\ClientCapabilitiesFactory()) extends \Fabricate\Client\ClientConfiguration {};

    expect($client->name())->toBe('versionless')
        ->and(fn () => $client->implementation('2026-07-28'))->toThrow(MCPMechanismException::class, 'ClientConfig');
});

it('matches the peer version when supported and falls back to its newest otherwise.', function () {
    $server = $this->panel->make(EchoServer::class);

    expect($server->matchProtocolVersion('2025-03-26'))->toBe('2025-03-26')
        ->and($server->matchProtocolVersion('1999-01-01'))->toBe('2026-07-28');
});

it('exposes nothing for a capability the concrete does not override.', function () {
    $server = $this->panel->make(EchoServer::class);
    $client = $this->panel->make(CuriousClient::class);

    expect($server->tools())->toBe(['echo', 'shout', 'enhashen', 'whisper', 'ponder'])
        ->and($server->prompts())->toBe(['greet'])
        ->and($server->resources())->toBe(['echo://ponderings', 'echo://reverse/{text}'])
        ->and($client->roots())->toBe(\App\Mcp\Roots\CuriousRoots::class)
        ->and(new class(new \Fabricate\Client\ClientCapabilitiesFactory()) extends \Fabricate\Client\ClientConfiguration {}->roots())->toBeNull();
});

it('names and describes a tool from its ToolCall attribute.', function () {
    $tool = new #[\Fabricate\Contracts\Mechanism\Attributes\ToolCall('probe', 'Probes things.')] class extends \Fabricate\Mechanism\Tool {};

    expect($tool::name())->toBe('probe')
        ->and($tool::description())->toBe('Probes things.')
        ->and($tool)->toBeInstanceOf(\Fabricate\Contracts\Mechanism\Tool::class);
});

it('refuses to name a tool without a ToolCall attribute.', function () {
    $tool = new class extends \Fabricate\Mechanism\Tool {};

    expect(fn() => $tool::name())->toThrow(MCPMechanismException::class);
});
