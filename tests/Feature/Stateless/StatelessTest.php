<?php

use Fabricate\Contracts\Mechanism\Attributes\ServerConfig;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\InputRequired;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Tool;
use Fabricate\Schema\V20260728\Capabilities\Elicitation\BooleanSchema;
use Fabricate\Schema\V20260728\Messaging\Elicitation\ElicitRequest;
use Fabricate\Schema\V20260728\Messaging\Elicitation\ElicitResult;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Fabricate\Schema\V20260728\Messaging\Params\ElicitRequestFormParams;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

#[ToolCall('confirm', 'Asks the user before it answers.')]
class ConfirmTool extends Tool
{
    public function __construct(public readonly string $what) {}

    public function handle(?InputResponses $input_responses = null, mixed $request_state = null): string|InputRequired
    {
        $answer = $input_responses?->responses['ok'] ?? null;

        if(! $answer instanceof ElicitResult) {
            return new InputRequired(['ok' => new ElicitRequest(new ElicitRequestFormParams("Go ahead with {$this->what}?", ['go' => new BooleanSchema('Go')]))], ['what' => $this->what]);
        }

        return "{$request_state['what']}: {$answer->action->value}";
    }
}

#[ToolCall('narrate', 'Logs at two levels and reports progress.')]
class NarrateTool extends Tool
{
    public function handle(RequestContext $context): string
    {
        $context->log(LoggingLevel::DEBUG, 'detail');
        $context->log(LoggingLevel::WARNING, 'careful');
        $context->progress(1, 1, 'done');

        return 'told';
    }
}

#[ServerConfig('modern', 'A 2026-07-28 server.', '2.0.0', instructions: 'Confirm before acting.')]
class ModernServer extends ServerConfiguration
{
    protected array $supported_protocol_versions = ['2025-11-25', '2026-07-28'];
    protected bool|array $tools = true;
    protected bool $logging = true;

    public function tools(mixed ...$params): array { return ['confirm', 'narrate']; }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php', 'app' => ['key' => 'base64:test-key']]));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', new SignalDispatcher($panel));
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));
    $tools->register(ConfirmTool::class);
    $tools->register(NarrateTool::class);

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    require __DIR__.'/../../../routes/mcp.php';

    $this->server = new MCPServer(new ModernServer(new ServerCapabilitiesFactory()), '2026-07-28');
});

afterEach(fn() => ControlPanel::setInstance(null));

/**
 * @param array<string, mixed> $meta Laid over the required protocol version and client capabilities.
 */
function modern(MCPServer $server, string $method, array $params = [], array $meta = [], int $id = 1): array
{
    $params['_meta'] = $meta + ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];

    return json_decode($server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params])))->toJson(), true);
}

it('describes itself on server/discover, uncached, with serverInfo in _meta.', function () {
    expect(modern($this->server, 'server/discover')['result'])->toEqual([
        '_meta' => ['io.modelcontextprotocol/serverInfo' => ['name' => 'modern', 'version' => '2.0.0', 'description' => 'A 2026-07-28 server.']],
        'resultType' => 'complete',
        'ttlMs' => 0,
        'cacheScope' => 'private',
        'supportedVersions' => ['2026-07-28'],
        'capabilities' => ['tools' => [], 'logging' => []],
        'instructions' => 'Confirm before acting.',
    ]);
});

it('names its serverInfo on every result.', function () {
    expect(modern($this->server, 'tools/list')['result']['_meta'])->toBe(['io.modelcontextprotocol/serverInfo' => ['name' => 'modern', 'version' => '2.0.0', 'description' => 'A 2026-07-28 server.']]);
});

it('refuses a request without its protocol version or client capabilities as invalid params.', function () {
    $without = json_decode($this->server->receiveMessage(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"tools/list"}'))->toJson(), true);

    expect($without['error']['code'])->toBe(-32602);
});

it('refuses another protocol version with UnsupportedProtocolVersionError listing the supported ones.', function () {
    expect(modern($this->server, 'tools/list', meta: ['io.modelcontextprotocol/protocolVersion' => '2025-11-25'])['error'])
        ->toBe(['code' => -32022, 'message' => 'Unsupported protocol version: [2025-11-25].', 'data' => ['supported' => ['2026-07-28'], 'requested' => '2025-11-25']]);
});

it('answers the methods 2026-07-28 removed as unknown.', function (string $method) {
    expect(modern($this->server, $method)['error']['code'])->toBe(-32601);
})->with(['initialize', 'ping', 'logging/setLevel', 'resources/subscribe']);

it('logs only for a request that names a level, at or above it.', function () {
    modern($this->server, 'tools/call', ['name' => 'narrate', 'arguments' => (object) []]);
    $silent = count($this->mail);

    modern($this->server, 'tools/call', ['name' => 'narrate', 'arguments' => (object) []], ['io.modelcontextprotocol/logLevel' => 'info']);

    expect($silent)->toBe(0)
        ->and(array_map(fn(OutboundMessage $mail) => $mail->message()->toArray()['params']['data'], $this->mail->getArrayCopy()))->toBe(['careful']);
});

it('reports progress for a request with a progress token.', function () {
    modern($this->server, 'tools/call', ['name' => 'narrate', 'arguments' => (object) []], ['progressToken' => 'p']);

    expect($this->mail[0]->message()->toArray())->toBe(['jsonrpc' => '2.0', 'method' => 'notifications/progress', 'params' => ['progressToken' => 'p', 'progress' => 1, 'total' => 1, 'message' => 'done']]);
});

it('asks for input with InputRequiredResult, then answers the retry with its verified state.', function () {
    $caps = ['io.modelcontextprotocol/clientCapabilities' => ['elicitation' => (object) []]];
    $first = modern($this->server, 'tools/call', ['name' => 'confirm', 'arguments' => ['what' => 'deploy']], $caps)['result'];

    expect($first['resultType'])->toBe('input_required')
        ->and($first['inputRequests']['ok']['method'])->toBe('elicitation/create')
        ->and($first['requestState'])->toBeString();

    $retry = modern($this->server, 'tools/call', ['name' => 'confirm', 'arguments' => ['what' => 'deploy'], 'requestState' => $first['requestState'], 'inputResponses' => ['ok' => ['action' => 'accept', 'content' => ['go' => true]]]], $caps, 2);

    expect($retry['result']['content'][0]['text'])->toBe('deploy: accept');
});

it('refuses to ask for input the client did not declare, with -32021 naming it.', function () {
    expect(modern($this->server, 'tools/call', ['name' => 'confirm', 'arguments' => ['what' => 'deploy']])['error'])
        ->toMatchArray(['code' => -32021, 'data' => ['requiredCapabilities' => ['elicitation' => []]]]);
});

it('refuses a retry whose requestState was altered or issued for another tool.', function (Closure $tamper) {
    $caps = ['io.modelcontextprotocol/clientCapabilities' => ['elicitation' => (object) []]];
    $state = modern($this->server, 'tools/call', ['name' => 'confirm', 'arguments' => ['what' => 'deploy']], $caps)['result']['requestState'];

    [$name, $sent] = $tamper($state);

    expect(modern($this->server, 'tools/call', ['name' => $name, 'arguments' => $name === 'confirm' ? ['what' => 'deploy'] : (object) [], 'requestState' => $sent], $caps, 2)['error']['code'])->toBe(-32602);
})->with([
    'altered' => [fn(string $state) => ['confirm', substr($state, 0, -2).'xx']],
    'another tool' => [fn(string $state) => ['narrate', $state]],
]);

it('keeps input_required to 2026-07-28: an earlier version gets an internal error.', function () {
    $server = new MCPServer(new ModernServer(new ServerCapabilitiesFactory()), '2025-11-25');
    $reply = json_decode($server->receiveMessage(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"confirm","arguments":{"what":"x"}}}'))->toJson(), true);

    expect($reply['error']['code'])->toBe(-32603);
});
