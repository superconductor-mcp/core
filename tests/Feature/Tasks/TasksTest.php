<?php

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Tool;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Vessel\ControlPanel;

#[ToolCall('tally', 'Counts the words in a message.')]
class TallyTool extends Tool
{
    protected static string $task_support = self::TASKS_OPTIONAL;

    public function __construct(public readonly string $message) {}

    public function handle(): string { return (string) str_word_count($this->message); }
}

#[ToolCall('brood', 'Thinks about a message for a while.')]
class BroodTool extends Tool
{
    protected static string $task_support = self::TASKS_OPTIONAL;

    protected bool $async = true;

    /** The call waiting to finish; the test finishes it, as the loop would once the work is done. */
    public static ?BroodTool $pending = null;

    public function __construct(public readonly string $message) {}

    public function handle(): ?string
    {
        if(! $this->isAsync()) return "pondered {$this->message}";

        static::$pending = $this;

        return null;
    }

    public function release(): void
    {
        $this->finish("pondered {$this->message}");
    }
}

#[ToolCall('only-task')]
class OnlyTaskTool extends Tool
{
    protected static string $task_support = self::TASKS_REQUIRED;

    public function handle(): string { return 'ran'; }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    foreach([TallyTool::class, BroodTool::class, OnlyTaskTool::class, \App\Mcp\Tools\EchoTool::class] as $tool) $tools->register($tool);
    BroodTool::$pending = null;

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function taskingServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;
        protected bool|array $tasks = ['list' => true, 'cancel' => true, 'requests' => ['tools' => ['call' => true]]];

        public function tools(mixed ...$params): array { return ['tally', 'brood', 'only-task', 'echo']; }
    };
}

function rpc(MCPServer $server, int $id, string $method, array $params = []): ?array
{
    $reply = $server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => $id, 'method' => $method] + ($params === [] ? [] : ['params' => $params]))));

    return is_null($reply) ? null : json_decode($reply->toJson(), true);
}

/** @return list<array> Every mailed message as the wire would carry it. */
function mailed(ArrayObject $mail): array
{
    return array_map(fn(OutboundMessage $message) => json_decode($message->message()->toJson(), true), $mail->getArrayCopy());
}

it('advertises which tools may run as tasks on 2025-11-25.', function () {
    $tools = array_column(rpc(new MCPServer(taskingServer(), '2025-11-25'), 1, 'tools/list')['result']['tools'], 'execution', 'name');

    expect($tools)->toBe(['tally' => ['taskSupport' => 'optional'], 'brood' => ['taskSupport' => 'optional'], 'only-task' => ['taskSupport' => 'required']]);
});

it('runs a sync tool as a task that is complete at once, and hands back its result with the related task.', function () {
    $server = new MCPServer(taskingServer(), '2025-11-25');

    $task = rpc($server, 1, 'tools/call', ['name' => 'tally', 'arguments' => ['message' => 'one two three'], 'task' => ['ttl' => 60000]])['result']['task'];

    expect($task['status'])->toBe('completed')
        ->and($task['ttl'])->toBe(60000)
        ->and(rpc($server, 2, 'tasks/get', ['taskId' => $task['taskId']])['result']['status'])->toBe('completed')
        ->and(rpc($server, 3, 'tasks/result', ['taskId' => $task['taskId']])['result'])->toBe([
            '_meta' => ['io.modelcontextprotocol/related-task' => ['taskId' => $task['taskId']]],
            'content' => [['type' => 'text', 'text' => '3']],
        ])
        ->and(mailed($this->mail)[0]['method'])->toBe('notifications/tasks/status');
});

it('runs an async tool as a working task, and answers a waiting tasks/result by mail once it finishes.', function () {
    $server = new MCPServer(taskingServer(), '2025-11-25');

    $task = rpc($server, 1, 'tools/call', ['name' => 'brood', 'arguments' => ['message' => 'tides'], 'task' => []])['result']['task'];

    expect($task['status'])->toBe('working')
        ->and(rpc($server, 2, 'tasks/result', ['taskId' => $task['taskId']]))->toBeNull()
        ->and($this->mail)->toHaveCount(0)
        ->and(array_column(rpc($server, 3, 'tasks/list')['result']['tasks'], 'status'))->toBe(['working']);

    BroodTool::$pending->release();

    // Settling the task answers the waiting tasks/result first, then the status notification goes out.
    [$answer, $status] = mailed($this->mail);

    expect($status['method'])->toBe('notifications/tasks/status')
        ->and($status['params']['status'])->toBe('completed')
        ->and($answer['id'])->toBe(2)
        ->and($answer['result']['content'])->toBe([['type' => 'text', 'text' => 'pondered tides']])
        ->and(rpc($server, 4, 'tasks/get', ['taskId' => $task['taskId']])['result']['status'])->toBe('completed');
});

it('cancels a working task and drops what it produces afterwards.', function () {
    $server = new MCPServer(taskingServer(), '2025-11-25');
    $task_id = rpc($server, 1, 'tools/call', ['name' => 'brood', 'arguments' => ['message' => 'x'], 'task' => []])['result']['task']['taskId'];

    expect(rpc($server, 2, 'tasks/cancel', ['taskId' => $task_id])['result']['status'])->toBe('cancelled');

    BroodTool::$pending->release();

    expect(rpc($server, 3, 'tasks/get', ['taskId' => $task_id])['result']['status'])->toBe('cancelled')
        ->and(rpc($server, 4, 'tasks/result', ['taskId' => $task_id])['error']['message'])->toBe("Task [{$task_id}] was cancelled.")
        ->and(rpc($server, 5, 'tasks/cancel', ['taskId' => $task_id])['error']['code'])->toBe(-32602);
});

it('refuses tasks for a tool that forbids them and plain calls for one that requires them.', function () {
    $server = new MCPServer(taskingServer(), '2025-11-25');

    expect(rpc($server, 1, 'tools/call', ['name' => 'echo', 'arguments' => ['message' => 'a'], 'task' => []])['error']['code'])->toBe(-32601)
        ->and(rpc($server, 2, 'tools/call', ['name' => 'only-task'])['error']['code'])->toBe(-32601)
        ->and(rpc($server, 3, 'tools/call', ['name' => 'only-task', 'task' => []])['result']['task']['status'])->toBe('completed');
});

it('lists and finds only the tasks of whoever asks, as the transport authenticated them.', function () {
    $configuration = taskingServer();
    $alice = new MCPServer($configuration, '2025-11-25', auth_identifier: 'alice');
    $bob = new MCPServer($configuration, '2025-11-25', auth_identifier: 'bob');

    $task_id = rpc($alice, 1, 'tools/call', ['name' => 'only-task', 'task' => []])['result']['task']['taskId'];

    expect(array_column(rpc($alice, 2, 'tasks/list')['result']['tasks'], 'taskId'))->toBe([$task_id])
        ->and(rpc($bob, 3, 'tasks/list')['result']['tasks'])->toBe([])
        ->and(rpc($bob, 4, 'tasks/get', ['taskId' => $task_id])['error']['code'])->toBe(-32602)
        ->and(rpc($bob, 5, 'tasks/cancel', ['taskId' => $task_id])['error']['code'])->toBe(-32602);
});

it('refuses an unknown task.', function () {
    expect(rpc(new MCPServer(taskingServer(), '2025-11-25'), 1, 'tasks/get', ['taskId' => 'nope'])['error']['code'])->toBe(-32602);
});

it('answers an async tool call by mail, and runs it in line when there is no session to post to.', function (string $version) {
    $server = new MCPServer(taskingServer(), $version);
    $params = ['name' => 'brood', 'arguments' => ['message' => 'waves']];
    if($version === '2026-07-28') $params['_meta'] = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];

    expect(rpc($server, 7, 'tools/call', $params))->toBeNull();

    BroodTool::$pending->release();

    expect(mailed($this->mail)[0]['id'])->toBe(7)
        ->and(mailed($this->mail)[0]['result']['content'])->toBe([['type' => 'text', 'text' => 'pondered waves']]);
})->with(['2024-11-05', '2025-06-18', '2025-11-25', '2026-07-28']);

it('runs an async tool in line when the router is called without a request context.', function () {
    $request = \Fabricate\Mechanism\MessageHydrator::hydrateAs(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"brood","arguments":{"message":"sand"}}}'), '2025-06-18');

    expect(\Fabricate\Core\Routing\ProcedureRouterFacade::dispatch('2025-06-18', $request, taskingServer())->toArray()['content'][0]['text'])->toBe('pondered sand');
});
