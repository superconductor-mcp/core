<?php

use App\Mcp\Clients\CuriousClient;
use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Client\MCPClient;
use Fabricate\Client\Roots;
use Fabricate\Client\Sampling;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolResult;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\SamplingErrorCode;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

/** Samples later: handle() returns nothing, and release() finishes with the result. */
class LaterSampling extends Sampling
{
    public static ?LaterSampling $pending = null;

    protected bool $async = true;

    public function handle(): void
    {
        self::$pending = $this;
    }

    public function release(string $protocol_version): void
    {
        $this->finish(match($protocol_version) {
            '2025-06-18' => new V20250618\Messaging\Sampling\CreateMessageResult('later-0', Role::ASSISTANT, new V20250618\Concerns\TextContent('done')),
            '2025-11-25' => new V20251125\Messaging\Sampling\CreateMessageResult('later-0', Role::ASSISTANT, new V20251125\Concerns\TextContent('done')),
        });
    }
}

/** A user who says no to every sample. */
class RefusingSampling extends Sampling
{
    public function handle(): never
    {
        throw new SuperconductorMCPException('User rejected sampling request', SamplingErrorCode::USER_REJECTED->value);
    }
}

/** Answers in the wrong version. */
class StaleRoots extends Roots
{
    public function handle(): Result
    {
        return new V20250618\Messaging\Roots\ListRootsResult([]);
    }
}

/**
 * A client whose concretes the test chooses.
 *
 * @param array<string, string> $concretes Concrete class by capability.
 */
function choosingClient(array $concretes, array $declared = []): ClientConfiguration
{
    $configuration = new #[\Fabricate\Contracts\Mechanism\Attributes\ClientConfig('chooser', version: '1.0.0')] class(new ClientCapabilitiesFactory()) extends ClientConfiguration {
        public array $concretes = [];
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];

        public function declare(array $declared): static
        {
            foreach($declared as $name => $value) $this->$name = $value;

            return $this;
        }

        public function roots(): ?string { return $this->concretes['roots'] ?? null; }
        public function sampling(): ?string { return $this->concretes['sampling'] ?? null; }
        public function elicitation(): ?string { return $this->concretes['elicitation'] ?? null; }
    };
    $configuration->concretes = $concretes;

    return $configuration->declare($declared);
}

beforeEach(function () {
    // CuriousRoots exposes the app's base path.
    ControlPanel::setInstance($panel = new class extends ControlPanel {
        public function basePath(string $path = ''): string { return '/apps/devbox'.($path === '' ? '' : "/{$path}"); }
    });
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', new SignalDispatcher($panel));

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    $this->reported = new ArrayObject();
    $handler = Mockery::mock(\Voyager\Contracts\Debug\ExceptionHandler::class);
    $handler->allows('report')->andReturnUsing(fn(Throwable $e) => $this->reported->append($e));
    $panel->registerInstance(\Voyager\Contracts\Debug\ExceptionHandler::class, $handler);

    require __DIR__.'/../../../routes/mcp.php';

    $this->curious = new CuriousClient(new ClientCapabilitiesFactory());
});

afterEach(fn() => ControlPanel::setInstance(null));

/**
 * What the client sends back for one message from the server, ready for the wire, decoded to compare.
 */
function answer(MCPClient $client, array $message): ?array
{
    $reply = $client->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', ...$message])));

    return $reply ? json_decode($reply->toJson(), true) : null;
}

function samplingParams(array $extra = []): array
{
    return ['messages' => [['role' => 'user', 'content' => ['type' => 'text', 'text' => 'hi']]], 'maxTokens' => 10, ...$extra];
}

function formParams(array $properties, array $required, ?string $mode = 'form'): array
{
    return array_filter(['mode' => $mode, 'message' => 'Tell me.', 'requestedSchema' => ['type' => 'object', 'properties' => $properties, 'required' => $required]]);
}

it('answers roots/list with the roots the developer defines, in each version.', function (string $version) {
    $reply = answer(new MCPClient($this->curious, $version), ['id' => 1, 'method' => 'roots/list']);

    expect($reply)->toBe(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['roots' => [['uri' => 'file://'.base_path(), 'name' => 'devbox']]]]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('answers sampling/createMessage from the developer\'s model, in each version.', function (string $version) {
    $reply = answer(new MCPClient($this->curious, $version), ['id' => 2, 'method' => 'sampling/createMessage', 'params' => samplingParams()]);

    expect($reply['result'])->toBe(['model' => 'curious-0', 'role' => 'assistant', 'content' => ['type' => 'text', 'text' => 'Why "hi"?'], 'stopReason' => 'endTurn']);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('answers elicitation/create from the developer\'s user: accepting with defaults, declining what it cannot fill.', function (string $version) {
    $client = new MCPClient($this->curious, $version);
    $mode = $version === '2025-06-18' ? null : 'form';

    // 2025-06-18 lets only a boolean field carry a default.
    $filled = answer($client, ['id' => 3, 'method' => 'elicitation/create', 'params' => formParams(['agree' => ['type' => 'boolean', 'default' => true]], ['agree'], $mode)]);
    $unfilled = answer($client, ['id' => 4, 'method' => 'elicitation/create', 'params' => formParams(['age' => ['type' => 'integer']], ['age'], $mode)]);

    expect($filled['result'])->toBe(['action' => 'accept', 'content' => ['agree' => true]])
        ->and($unfilled['result'])->toBe(['action' => 'decline']);
})->with(['2025-06-18', '2025-11-25']);

it('refuses what the client did not declare.', function (string $version, array $message, int $code) {
    expect(answer(new MCPClient($this->curious, $version), $message)['error']['code'])->toBe($code);
})->with([
    'elicitation before 2025-06-18' => ['2025-03-26', ['id' => 5, 'method' => 'elicitation/create', 'params' => formParams([], [], null)], -32601],
    'a url elicitation to a form-only client' => ['2025-11-25', ['id' => 5, 'method' => 'elicitation/create', 'params' => ['mode' => 'url', 'message' => 'Sign in.', 'url' => 'https://example.com', 'elicitationId' => 'e1']], -32602],
    'tool use in sampling without sampling.tools' => ['2025-11-25', ['id' => 5, 'method' => 'sampling/createMessage', 'params' => samplingParams(['tools' => [['name' => 't', 'inputSchema' => ['type' => 'object']]]])], -32602],
    'a client method the server can\'t call' => ['2025-11-25', ['id' => 5, 'method' => 'tools/list'], -32601],
]);

it('lists no roots when the client declares roots but names no concrete, in each version and as a 2026-07-28 input.', function () {
    $declared = choosingClient([], ['roots' => true]);

    expect(answer(new MCPClient($declared, '2024-11-05'), ['id' => 1, 'method' => 'roots/list'])['result'])->toBe(['roots' => []])
        ->and(answer(new MCPClient($declared, '2025-11-25'), ['id' => 1, 'method' => 'roots/list'])['result'])->toBe(['roots' => []]);

    $client = new MCPClient($declared, '2026-07-28');
    modernCall($client, $declared);
    answer($client, ['id' => 1, 'result' => ['resultType' => 'input_required', 'inputRequests' => ['where' => ['method' => 'roots/list']]]]);

    expect(json_decode($client->pullQueued()[0]->toJson(), true)['params']['inputResponses']['where'])->toBe(['roots' => []]);
});

it('refuses roots/list with -32601 when the client declares no roots.', function () {
    expect(answer(new MCPClient(choosingClient([]), '2025-06-18'), ['id' => 1, 'method' => 'roots/list'])['error']['code'])->toBe(-32601);
});

it('answers a user\'s rejection with the sampling page\'s -1.', function () {
    $client = new MCPClient(choosingClient(['sampling' => RefusingSampling::class], ['sampling' => true]), '2025-06-18');

    expect(answer($client, ['id' => 6, 'method' => 'sampling/createMessage', 'params' => samplingParams()])['error'])->toBe(['code' => -1, 'message' => 'User rejected sampling request']);
});

it('reports a concrete that answers in another version, and tells the server only that something went wrong.', function () {
    $client = new MCPClient(choosingClient(['roots' => StaleRoots::class], ['roots' => true]), '2025-11-25');

    expect(answer($client, ['id' => 7, 'method' => 'roots/list'])['error'])->toBe(['code' => -32603, 'message' => 'Internal error'])
        ->and($this->reported[0]->getMessage())->toContain('must answer with');
});

it('answers later by mail when the concrete runs async.', function () {
    $client = new MCPClient(choosingClient(['sampling' => LaterSampling::class], ['sampling' => true]), '2025-06-18');

    expect(answer($client, ['id' => 8, 'method' => 'sampling/createMessage', 'params' => samplingParams()]))->toBeNull()
        ->and($this->mail)->toHaveCount(0);

    LaterSampling::$pending->release('2025-06-18');

    expect($this->mail[0]->id->id)->toBe(8)
        ->and($this->mail[0]->message()->toArray()['result']['content']['text'])->toBe('done');
});

it('runs sampling and elicitation as 2025-11-25 tasks when asked, and serves them through tasks/*.', function () {
    $client = new MCPClient($this->curious, '2025-11-25');

    $created = answer($client, ['id' => 9, 'method' => 'sampling/createMessage', 'params' => samplingParams(['task' => ['ttl' => 60000]])])['result']['task'];
    $id = $created['taskId'];

    expect($created['status'])->toBe('completed')
        ->and(answer($client, ['id' => 10, 'method' => 'tasks/get', 'params' => ['taskId' => $id]])['result']['status'])->toBe('completed')
        ->and(answer($client, ['id' => 11, 'method' => 'tasks/result', 'params' => ['taskId' => $id]])['result']['content']['text'])->toBe('Why "hi"?')
        ->and(array_column(answer($client, ['id' => 12, 'method' => 'tasks/list'])['result']['tasks'], 'taskId'))->toBe([$id])
        ->and($this->mail[0]->message()->toArray()['method'])->toBe('notifications/tasks/status')
        ->and(answer($client, ['id' => 13, 'method' => 'elicitation/create', 'params' => formParams(['name' => ['type' => 'string', 'default' => 'Ana']], ['name']) + ['task' => (object) []]])['result']['task']['status'])->toBe('completed');
});

it('answers a task request in line when the client does not offer tasks for it.', function () {
    $client = new MCPClient(choosingClient(['sampling' => App\Mcp\Sampling\CuriousSampling::class], ['sampling' => true]), '2025-11-25');

    expect(answer($client, ['id' => 14, 'method' => 'sampling/createMessage', 'params' => samplingParams(['task' => (object) []])])['result']['model'])->toBe('curious-0');
});

it('sends notifications/roots/list_changed when the roots change, if it declared listChanged and its version has it.', function () {
    $listening = new MCPClient($this->curious, '2025-06-18');
    $quiet = new MCPClient(choosingClient(['roots' => App\Mcp\Roots\CuriousRoots::class], ['roots' => true]), '2025-06-18');
    $modern = new MCPClient($this->curious, '2026-07-28');
    $closed = new MCPClient($this->curious, '2025-11-25');
    $closed->close();

    App\Mcp\Roots\CuriousRoots::changed();

    expect(array_map(fn($m) => $m->toArray(), $listening->pullQueued()))->toBe([['jsonrpc' => '2.0', 'method' => 'notifications/roots/list_changed']])
        ->and($quiet->pullQueued())->toBe([])
        ->and($modern->pullQueued())->toBe([])
        ->and($closed->pullQueued())->toBe([]);
});

/**
 * A 2026-07-28 tools/call from CuriousClient, sent with $then.
 */
function modernCall(MCPClient $client, ClientConfiguration $configuration, ?Closure $then = null): void
{
    $meta = new V20260728\Messaging\MetaObjects\RequestMetaObject('2026-07-28', $configuration->capabilities('2026-07-28'), $configuration->implementation('2026-07-28'));
    $client->request(new V20260728\Messaging\Tools\CallToolRequest(new RequestId(1), new V20260728\Messaging\Params\CallToolRequestParams('ask', $meta, ['q' => 'x'])), then: $then);
    $client->pullQueued();
}

it('on 2026-07-28, fulfils every input request from its concretes and retries with a new id, echoing requestState.', function () {
    $client = new MCPClient($this->curious, '2026-07-28');
    $got = null;
    modernCall($client, $this->curious, function($r) use (&$got) { $got = $r; });

    answer($client, ['id' => 1, 'result' => ['resultType' => 'input_required', 'requestState' => 'sealed', 'inputRequests' => [
        'where' => ['method' => 'roots/list'],
        'think' => ['method' => 'sampling/createMessage', 'params' => samplingParams()],
        'who' => ['method' => 'elicitation/create', 'params' => formParams(['name' => ['type' => 'string', 'default' => 'Ana']], ['name'])],
    ]]]);

    $retry = json_decode($client->pullQueued()[0]->toJson(), true);

    expect($retry['id'])->not->toBe(1)
        ->and($retry['method'])->toBe('tools/call')
        ->and($retry['params']['name'])->toBe('ask')
        ->and($retry['params']['arguments'])->toBe(['q' => 'x'])
        ->and($retry['params']['requestState'])->toBe('sealed')
        ->and($retry['params']['inputResponses']['where']['roots'][0]['name'])->toBe('devbox')
        ->and($retry['params']['inputResponses']['think']['model'])->toBe('curious-0')
        ->and($retry['params']['inputResponses']['who'])->toBe(['action' => 'accept', 'content' => ['name' => 'Ana']])
        ->and($got)->toBeNull();

    answer($client, ['id' => $retry['id'], 'result' => ['resultType' => 'complete', 'content' => [['type' => 'text', 'text' => 'thanks']]]]);

    expect($got)->toBeInstanceOf(CallToolResult::class);
});

it('on 2026-07-28, ends the request with the error when an input request asks for something the client did not declare.', function () {
    $client = new MCPClient($this->curious, '2026-07-28');
    $got = null;
    modernCall($client, $this->curious, function($r) use (&$got) { $got = $r; });

    answer($client, ['id' => 1, 'result' => ['resultType' => 'input_required', 'inputRequests' => [
        'go' => ['method' => 'elicitation/create', 'params' => ['mode' => 'url', 'message' => 'Sign in.', 'url' => 'https://example.com']],
    ]]]);

    expect($got)->toBeInstanceOf(MCPError::class)
        ->and($got->toArray()['code'])->toBe(-32602)
        ->and($client->pullQueued())->toBe([]);
});

class MixedRoots extends Roots
{
    public function handle(string $protocol_version): Result
    {
        return new V20250618\Messaging\Roots\ListRootsResult(array_map(fn(string $uri) => new V20250618\Capabilities\Roots\Root($uri), [
            'file:///home/ana/project', 'https://example.com/repo', 'file:///home/ana/../bob', 'file:///home/%2e%2e/etc', 'file:relative/path', 'file:///home/ana/notes',
        ]));
    }
}

it('exposes only file:// roots with an absolute path and no . or .. segments, and reports the rest.', function () {
    $roots = answer(new MCPClient(choosingClient(['roots' => MixedRoots::class], ['roots' => true]), '2025-06-18'), ['id' => 1, 'method' => 'roots/list'])['result']['roots'];

    expect(array_column($roots, 'uri'))->toBe(['file:///home/ana/project', 'file:///home/ana/notes'])
        ->and(array_map(fn(Throwable $e) => explode(']', $e->getMessage())[0], $this->reported->getArrayCopy()))
        ->toBe(['Root [https://example.com/repo', 'Root [file:///home/ana/../bob', 'Root [file:///home/%2e%2e/etc', 'Root [file:relative/path']);
});

it('refuses sampling whose tool results break the sampling page\'s rules with -32602.', function (array $messages, ?int $code) {
    $client = new MCPClient(choosingClient(['sampling' => \App\Mcp\Sampling\CuriousSampling::class], ['sampling' => ['tools' => true]]), '2025-11-25');
    $reply = answer($client, ['id' => 1, 'method' => 'sampling/createMessage', 'params' => ['messages' => $messages, 'maxTokens' => 10, 'tools' => [['name' => 't', 'inputSchema' => ['type' => 'object']]]]]);

    expect($reply['error']['code'] ?? null)->toBe($code);
})->with([
    'tool result answered' => [[
        ['role' => 'user', 'content' => ['type' => 'text', 'text' => 'go']],
        ['role' => 'assistant', 'content' => [['type' => 'tool_use', 'id' => 'u1', 'name' => 't', 'input' => (object) []]]],
        ['role' => 'user', 'content' => [['type' => 'tool_result', 'toolUseId' => 'u1', 'content' => []]]],
    ], null],
    'tool result missing' => [[
        ['role' => 'user', 'content' => ['type' => 'text', 'text' => 'go']],
        ['role' => 'assistant', 'content' => [['type' => 'tool_use', 'id' => 'u1', 'name' => 't', 'input' => (object) []]]],
        ['role' => 'user', 'content' => ['type' => 'text', 'text' => 'and?']],
    ], -32602],
    'tool result mixed with text' => [[
        ['role' => 'user', 'content' => ['type' => 'text', 'text' => 'go']],
        ['role' => 'assistant', 'content' => [['type' => 'tool_use', 'id' => 'u1', 'name' => 't', 'input' => (object) []]]],
        ['role' => 'user', 'content' => [['type' => 'tool_result', 'toolUseId' => 'u1', 'content' => []], ['type' => 'text', 'text' => 'also']]],
    ], -32602],
]);

it('passes on notifications/elicitation/complete only for a URL elicitation still open with it, once.', function () {
    $client = new MCPClient(choosingClient(['elicitation' => \App\Mcp\Elicitation\CuriousElicitation::class], ['elicitation' => ['url' => true]]), '2025-11-25');
    $seen = new ArrayObject();
    app('signals')->listen(\Fabricate\Client\Signals\NotificationReceived::class, fn($signal) => $seen->append($signal->notification->toArray()['params']['elicitationId']));
    $complete = fn(string $id) => answer($client, ['method' => 'notifications/elicitation/complete', 'params' => ['elicitationId' => $id]]);

    $client->request(new \Fabricate\Schema\V20251125\Messaging\Tools\CallToolRequest(new \Fabricate\Contracts\Schema\RequestId(9), new \Fabricate\Schema\V20251125\Messaging\Params\CallToolRequestParams('connect')));
    answer($client, ['id' => 9, 'error' => ['code' => -32042, 'message' => 'Authorize first.', 'data' => ['elicitations' => [['mode' => 'url', 'elicitationId' => 'from-error', 'url' => 'https://example.com/a', 'message' => 'Authorize']]]]]);
    answer($client, ['id' => 1, 'method' => 'elicitation/create', 'params' => ['mode' => 'url', 'elicitationId' => 'from-request', 'url' => 'https://example.com/b', 'message' => 'Sign in']]);

    foreach(['from-request', 'from-request', 'unknown', 'from-error'] as $id) $complete($id);

    expect($seen->getArrayCopy())->toBe(['from-request', 'from-error']);
});
