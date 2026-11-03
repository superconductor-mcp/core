<?php

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\InputRequired;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
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

#[ToolCall('steep', 'Answers once the test releases it.')]
class SteepTool extends Tool
{
    protected static string $task_support = self::TASKS_OPTIONAL;
    protected bool $async = true;

    public static ?SteepTool $pending = null;

    public function handle(?InputResponses $input_responses = null, mixed $request_state = null): ?string
    {
        if(! is_null($input_responses)) {
            $answer = $input_responses->responses['sure'];

            $this->finish("steeped, {$request_state['for']} said {$answer->action->value}");

            return null;
        }

        static::$pending = $this;

        return null;
    }

    public function release(): void { $this->finish('steeped'); }

    public function ask(): void
    {
        $this->finish(new InputRequired(['sure' => new ElicitRequest(new ElicitRequestFormParams('Steep longer?', ['longer' => new BooleanSchema()]))], ['for' => 'tea']));
    }
}

#[ToolCall('only-tasks')]
class OnlyTasksTool extends Tool
{
    protected static string $task_support = self::TASKS_REQUIRED;

    public function handle(): string { return 'ran'; }
}

#[ToolCall('ask-first')]
class AskFirstTool extends Tool
{
    protected static string $task_support = self::TASKS_OPTIONAL;

    public function handle(?InputResponses $input_responses = null): string|InputRequired
    {
        return $input_responses ? 'asked' : new InputRequired(['go' => new ElicitRequest(new ElicitRequestFormParams('Go?', ['go' => new BooleanSchema()]))]);
    }
}

class TaskRunningServer extends ServerConfiguration
{
    protected array $supported_protocol_versions = ['2026-07-28'];
    protected bool|array $tools = true;
    protected bool|array $tasks = ['cancel' => true, 'requests' => ['tools' => ['call' => true]]];

    public function tools(mixed ...$params): array { return ['steep', 'only-tasks', 'ask-first']; }
}

const TASKS = ['io.modelcontextprotocol/clientCapabilities' => ['extensions' => ['io.modelcontextprotocol/tasks' => []], 'elicitation' => []]];

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php', 'app' => ['key' => 'base64:test-key']]));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', new SignalDispatcher($panel));
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));
    foreach([SteepTool::class, OnlyTasksTool::class, AskFirstTool::class] as $tool) $tools->register($tool);
    SteepTool::$pending = null;

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    require __DIR__.'/../../../routes/mcp.php';

    $this->server = new MCPServer(new TaskRunningServer(new ServerCapabilitiesFactory()), '2026-07-28');
});

afterEach(fn() => ControlPanel::setInstance(null));

function tasked(MCPServer $server, string $method, array $params = [], array $meta = TASKS, int $id = 1): ?array
{
    $params['_meta'] = $meta + ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];
    $reply = $server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params])));

    return is_null($reply) ? null : json_decode($reply->toJson(), true);
}

it('declares the tasks extension in its capabilities.', function () {
    expect(tasked($this->server, 'server/discover')['result']['capabilities']['extensions'])->toBe(['io.modelcontextprotocol/tasks' => []]);
});

it('runs a call as a task for a client that declared the extension, and reports it through tasks/get.', function () {
    $handle = tasked($this->server, 'tools/call', ['name' => 'steep', 'arguments' => (object) []])['result'];

    expect($handle['resultType'])->toBe('task')
        ->and($handle['status'])->toBe('working')
        ->and(array_keys($handle))->toContain('taskId', 'createdAt', 'lastUpdatedAt', 'ttlMs', 'pollIntervalMs')
        ->and(tasked($this->server, 'tasks/get', ['taskId' => $handle['taskId']])['result']['status'])->toBe('working');

    SteepTool::$pending->release();
    $done = tasked($this->server, 'tasks/get', ['taskId' => $handle['taskId']], id: 2)['result'];

    expect($done['resultType'])->toBe('complete')
        ->and($done['status'])->toBe('completed')
        ->and($done['result']['content'])->toBe([['type' => 'text', 'text' => 'steeped']]);
});

it('answers a client without the extension directly, and refuses it a tool that only runs as a task.', function () {
    $plain = tasked($this->server, 'tools/call', ['name' => 'ask-first', 'arguments' => (object) []], ['io.modelcontextprotocol/clientCapabilities' => ['elicitation' => []]]);
    $refused = tasked($this->server, 'tools/call', ['name' => 'only-tasks', 'arguments' => (object) []], []);

    expect($plain['result']['resultType'])->toBe('input_required')
        ->and($refused['error'])->toMatchArray(['code' => -32021, 'data' => ['requiredCapabilities' => ['extensions' => ['io.modelcontextprotocol/tasks' => []]]]]);
});

it('settles the exchange before any task exists for a tool that asks for input at once.', function () {
    $first = tasked($this->server, 'tools/call', ['name' => 'ask-first', 'arguments' => (object) []])['result'];

    expect($first['resultType'])->toBe('input_required')
        ->and($this->server->configuration->taskStore()->all())->toBe([]);
});

it('moves a task to input_required for input asked later, and resumes it once tasks/update answers.', function () {
    $id = tasked($this->server, 'tools/call', ['name' => 'steep', 'arguments' => (object) []])['result']['taskId'];
    SteepTool::$pending->ask();

    $waiting = tasked($this->server, 'tasks/get', ['taskId' => $id], id: 2)['result'];

    expect($waiting['status'])->toBe('input_required')
        ->and($waiting['inputRequests']['sure']['method'])->toBe('elicitation/create');

    expect(tasked($this->server, 'tasks/update', ['taskId' => $id, 'inputResponses' => ['other' => ['action' => 'accept']]], id: 3)['result'])->toBe(['resultType' => 'complete'])
        ->and(tasked($this->server, 'tasks/get', ['taskId' => $id], id: 4)['result']['status'])->toBe('input_required');

    tasked($this->server, 'tasks/update', ['taskId' => $id, 'inputResponses' => ['sure' => ['action' => 'accept', 'content' => ['longer' => true]]]], id: 5);

    $done = tasked($this->server, 'tasks/get', ['taskId' => $id], id: 6)['result'];

    expect($done['status'])->toBe('completed')
        ->and($done['result']['content'][0]['text'])->toBe('steeped, tea said accept');
});

it('acknowledges tasks/cancel and keeps a cancelled task cancelled.', function () {
    $id = tasked($this->server, 'tools/call', ['name' => 'steep', 'arguments' => (object) []])['result']['taskId'];

    expect(tasked($this->server, 'tasks/cancel', ['taskId' => $id], id: 2)['result'])->toBe(['resultType' => 'complete']);

    SteepTool::$pending->release();

    expect(tasked($this->server, 'tasks/get', ['taskId' => $id], id: 3)['result']['status'])->toBe('cancelled');
});

it('refuses an unknown task with -32602, and a client without the extension with -32021.', function (string $method) {
    $params = ['taskId' => 'nope'] + ($method === 'tasks/update' ? ['inputResponses' => (object) []] : []);

    expect(tasked($this->server, $method, $params)['error']['code'])->toBe(-32602)
        ->and(tasked($this->server, $method, $params, [])['error']['code'])->toBe(-32021);
})->with(['tasks/get', 'tasks/update', 'tasks/cancel']);

it('has no tasks/list or tasks/result in 2026-07-28.', function (string $method) {
    expect(tasked($this->server, $method, ['taskId' => 'x'])['error']['code'])->toBe(-32601);
})->with(['tasks/list', 'tasks/result']);

it('sends notifications/tasks to a subscription that named the task, and refuses task ids without the extension.', function () {
    $id = tasked($this->server, 'tools/call', ['name' => 'steep', 'arguments' => (object) []])['result']['taskId'];

    expect(tasked($this->server, 'subscriptions/listen', ['notifications' => ['taskIds' => [$id]]], [], 2)['error']['code'])->toBe(-32021);

    tasked($this->server, 'subscriptions/listen', ['notifications' => ['taskIds' => [$id, 'unknown']]], id: 3);
    $ack = $this->mail[0]->message()->toArray()['params'];
    $this->mail->exchangeArray([]);

    SteepTool::$pending->release();
    $notice = $this->mail[0]->message()->toArray();

    expect($ack['notifications'])->toBe(['taskIds' => [$id]])
        ->and($notice['method'])->toBe('notifications/tasks')
        ->and($notice['params']['_meta'])->toBe(['io.modelcontextprotocol/subscriptionId' => 3])
        ->and($notice['params']['status'])->toBe('completed')
        ->and($notice['params']['result']['content'][0]['text'])->toBe('steeped');
});
