<?php

use App\Mcp\Tools\EchoTool;
use App\Mcp\Tools\EnhashenTool;
use App\Mcp\Tools\ShoutTool;
use App\Mcp\Tools\WhisperTool;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Tool;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

#[ToolCall('explode', 'Always fails.')]
class ExplodingTool extends Tool
{
    public function handle(): string { throw new RuntimeException('The tool blew up.'); }
}

#[ToolCall('version', 'Says which configuration and version it ran under.')]
class VersionTool extends Tool
{
    public function handle(ServerConfiguration $server, string $protocol_version): array
    {
        return [$server->name(), $protocol_version, ['ok' => true]];
    }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));

    foreach([EchoTool::class, ShoutTool::class, EnhashenTool::class, WhisperTool::class, ExplodingTool::class, VersionTool::class] as $tool) $tools->register($tool);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function callServer(): ServerConfiguration
{
    return new #[\Fabricate\Contracts\Mechanism\Attributes\ServerConfig('caller')] class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;

        public function tools(mixed ...$params): array { return ['echo', 'shout', 'enhashen', 'whisper', 'explode', 'version']; }
    };
}

function invokeTool(string $version, string $name, mixed $arguments = null): array
{
    $params = ['name' => $name] + (is_null($arguments) ? [] : ['arguments' => $arguments]);
    if($version === '2026-07-28') $params['_meta'] = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];

    $wire = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => $params]);

    return json_decode(new MCPServer(callServer(), $version)->receiveMessage(MessageDecoder::fromWire($wire))->toJson(), true);
}

it('runs every app tool and returns its text.', function (string $version) {
    expect(invokeTool($version, 'echo', ['message' => 'Hi There'])['result']['content'])->toBe([['type' => 'text', 'text' => 'Hi There']])
        ->and(invokeTool($version, 'shout', ['message' => 'Hi There'])['result']['content'][0]['text'])->toBe('HI THERE')
        ->and(invokeTool($version, 'whisper', ['message' => 'Hi There'])['result']['content'][0]['text'])->toBe('hi there')
        ->and(invokeTool($version, 'enhashen', ['message' => 'Hi There'])['result']['content'][0]['text'])->toBe(hash('sha256', 'Hi There'));
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('injects the configuration and protocol version into handle(), and renders each item of a list.', function () {
    expect(array_column(invokeTool('2025-06-18', 'version')['result']['content'], 'text'))->toBe(['caller', '2025-06-18', '{"ok":true}']);
});

it('reports a failure inside the tool as an error result.', function (string $version) {
    expect(invokeTool($version, 'explode')['result'])->toBe(['content' => [['type' => 'text', 'text' => 'The tool blew up.']], 'isError' => true]);
})->with(['2024-11-05', '2025-11-25']);

it('refuses a tool the server does not offer.', function () {
    expect(invokeTool('2025-06-18', 'search')['error']['code'])->toBe(-32602);
});

it('refuses bad arguments as a protocol error until 2025-06-18.', function (mixed $arguments) {
    expect(invokeTool('2025-06-18', 'echo', $arguments)['error']['code'])->toBe(-32602);
})->with([
    'missing' => [[]],
    'wrong type' => [['message' => 5]],
    'unknown name' => [['message' => 'a', 'loud' => true]],
    'by position' => [['a']],
]);

it('reports bad arguments as an error result from 2025-11-25.', function (string $version) {
    $result = invokeTool($version, 'echo', ['message' => 5])['result'];

    expect($result['isError'])->toBeTrue()
        ->and($result['content'][0]['text'])->toBe('Invalid arguments for tool [echo]: App\\Mcp\\Tools\\EchoTool::__construct(): Argument #1 ($message) must be of type string, int given');
})->with(['2025-11-25', '2026-07-28']);
