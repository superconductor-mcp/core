<?php

use App\Mcp\Tools\EchoTool;
use App\Mcp\Tools\ShoutTool;
use App\Mcp\Tools\WhisperTool;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', $this->config = new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));

    foreach([EchoTool::class, ShoutTool::class, WhisperTool::class] as $tool) $tools->register($tool);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function toolServer(array $tools): ServerConfiguration
{
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;
        public array $offered = [];

        public function tools(mixed ...$params): array { return $this->offered; }
    };
    $server->offered = $tools;

    return $server;
}

function listTools(ServerConfiguration $server, string $version, string $params = ''): array
{
    $wire = '{"jsonrpc":"2.0","id":1,"method":"tools/list"'.($params === '' ? '' : ',"params":'.$params).'}';

    return json_decode(new MCPServer($server, $version)->receiveMessage(MessageDecoder::fromWire($wire))->toJson(), true);
}

it('lists the tools the server names, defined from their attribute and constructor.', function (string $version) {
    expect(listTools(toolServer(['echo', 'whisper']), $version)['result']['tools'])->toBe([
        ['name' => 'echo', 'description' => 'Send back the message as given.', 'inputSchema' => ['type' => 'object', 'properties' => ['message' => ['type' => 'string']], 'required' => ['message']]],
        ['name' => 'whisper', 'description' => 'Send back the message in lower case.', 'inputSchema' => ['type' => 'object', 'properties' => ['message' => ['type' => 'string']], 'required' => ['message']]],
    ]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('pages the list behind a cursor.', function () {
    $this->config->set('mcp.pagination.page_size', 2);
    $server = toolServer(['echo', 'shout', 'whisper']);

    $first = listTools($server, '2025-06-18')['result'];
    $second = listTools($server, '2025-06-18', json_encode(['cursor' => $first['nextCursor']]))['result'];

    expect(array_column($first['tools'], 'name'))->toBe(['echo', 'shout'])
        ->and(array_column($second['tools'], 'name'))->toBe(['whisper'])
        ->and($second)->not->toHaveKey('nextCursor');
});

it('refuses a cursor it did not issue.', function () {
    expect(listTools(toolServer(['echo']), '2025-06-18', '{"cursor":"garbage"}')['error'])
        ->toBe(['code' => -32602, 'message' => 'Invalid cursor.']);
});

it('fails loudly when the server names a tool nobody registered.', function () {
    expect(listTools(toolServer(['echo', 'search']), '2025-06-18')['error'])
        ->toBe(['code' => -32603, 'message' => 'Unknown tool [search].']);
});

it('lists tools in 2026-07-28 as a result no one caches.', function () {
    $result = listTools(toolServer(['shout']), '2026-07-28', '{"_meta":{"io.modelcontextprotocol/protocolVersion":"2026-07-28","io.modelcontextprotocol/clientCapabilities":{}}}')['result'];

    expect($result['tools'])->toBe([
        ['name' => 'shout', 'description' => 'Send back the message in upper case.', 'inputSchema' => ['properties' => ['message' => ['type' => 'string']], 'required' => ['message'], 'type' => 'object']],
    ])->and($result['ttlMs'])->toBe(0)
        ->and($result['cacheScope'])->toBe('private');
});
