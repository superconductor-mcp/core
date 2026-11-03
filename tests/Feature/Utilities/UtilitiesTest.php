<?php

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Tool;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

#[ToolCall('chatter', 'Logs and reports progress, then answers when released.')]
class ChatterTool extends Tool
{
    protected bool $async = true;

    public static ?ChatterTool $pending = null;

    public static ?RequestContext $context = null;

    public function handle(RequestContext $context): ?string
    {
        $context->log(LoggingLevel::DEBUG, 'warming up', 'chatter');
        $context->log(LoggingLevel::ERROR, ['problem' => 'none'], 'chatter');
        $context->progress(1, 2, 'halfway');

        if(! $this->isAsync()) return 'said';

        static::$pending = $this;
        static::$context = $context;

        return null;
    }

    public function release(): void
    {
        static::$context->progress(2, 2, 'done');
        $this->finish('said');
    }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', new SignalDispatcher($panel));
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));
    $tools->register(ChatterTool::class);

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    ChatterTool::$pending = null;

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function chattyServer(bool $logging = true): ServerConfiguration
{
    $server = $logging
        ? new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
            protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25'];
            protected bool|array $tools = true;
            protected bool $logging = true;
            public function tools(mixed ...$params): array { return ['chatter']; }
        }
        : new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
            protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25'];
            protected bool|array $tools = true;
            public function tools(mixed ...$params): array { return ['chatter']; }
        };

    return $server;
}

function utilitySend(MCPServer $server, array $message): ?array
{
    $reply = $server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0'] + $message)));

    return is_null($reply) ? null : json_decode($reply->toJson(), true);
}

/** @return list<array{0: ?int, 1: array}> Each mailed message with the request it belongs to: its own id for a response, related_request for a notification. */
function utilityMail(ArrayObject $mail): array
{
    return array_map(fn(OutboundMessage $message) => [($message->id ?? $message->related_request)?->id, json_decode($message->message()->toJson(), true)], $mail->getArrayCopy());
}

function chatterCall(int $id, ?string $token = null): array
{
    return ['id' => $id, 'method' => 'tools/call', 'params' => ['name' => 'chatter', 'arguments' => (object) []] + ($token ? ['_meta' => ['progressToken' => $token]] : [])];
}

it('sends log messages at or above the level the client set, tied to the request they were logged in.', function (string $version) {
    $server = new MCPServer(chattyServer(), $version);
    utilitySend($server, ['id' => 1, 'method' => 'logging/setLevel', 'params' => ['level' => 'warning']]);

    expect(utilitySend($server, chatterCall(2)))->toBeNull();

    [[$request, $message]] = utilityMail($this->mail);

    expect($request)->toBe(2)
        ->and($message)->toEqual(['jsonrpc' => '2.0', 'method' => 'notifications/message', 'params' => ['level' => 'error', 'logger' => 'chatter', 'data' => ['problem' => 'none']]]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('sends every level until the client sets one, and none when the configuration does not declare logging.', function () {
    utilitySend(new MCPServer(chattyServer(), '2025-06-18'), chatterCall(1));
    $levels = array_map(fn(array $mail) => $mail[1]['params']['level'] ?? null, utilityMail($this->mail));

    $this->mail->exchangeArray([]);
    utilitySend(new MCPServer(chattyServer(logging: false), '2025-06-18'), chatterCall(1));

    expect($levels)->toBe(['debug', 'error'])
        ->and($this->mail)->toHaveCount(0);
});

it('reports progress only when the request carried a progress token, in each version\'s shape.', function (string $version, array $params) {
    $server = new MCPServer(chattyServer(logging: false), $version);

    utilitySend($server, chatterCall(1));
    expect($this->mail)->toHaveCount(0);

    utilitySend($server, chatterCall(2, 'tok'));

    expect(utilityMail($this->mail))->toBe([[2, ['jsonrpc' => '2.0', 'method' => 'notifications/progress', 'params' => $params]]]);
})->with([
    ['2024-11-05', ['progressToken' => 'tok', 'progress' => 1, 'total' => 2]],
    ['2025-03-26', ['progressToken' => 'tok', 'progress' => 1, 'total' => 2, 'message' => 'halfway']],
    ['2025-06-18', ['progressToken' => 'tok', 'progress' => 1, 'total' => 2, 'message' => 'halfway']],
    ['2025-11-25', ['progressToken' => 'tok', 'progress' => 1, 'total' => 2, 'message' => 'halfway']],
]);

it('sends nothing more for a request the client cancelled while its answer was coming.', function (string $version) {
    $server = new MCPServer(chattyServer(logging: false), $version);

    utilitySend($server, chatterCall(1, 'tok'));
    utilitySend($server, ['method' => 'notifications/cancelled', 'params' => ['requestId' => 1, 'reason' => 'changed my mind']]);
    $this->mail->exchangeArray([]);

    ChatterTool::$pending->release();

    expect(ChatterTool::$context->cancelled())->toBeTrue()
        ->and($this->mail)->toHaveCount(0);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('leaves alone a request answered before the cancel arrived, and one it never saw.', function () {
    $server = new MCPServer(chattyServer(logging: false), '2025-06-18');

    utilitySend($server, chatterCall(1));
    ChatterTool::$pending->release();
    $context = ChatterTool::$context;

    utilitySend($server, ['method' => 'notifications/cancelled', 'params' => ['requestId' => 1]]);
    utilitySend($server, ['method' => 'notifications/cancelled', 'params' => ['requestId' => 99]]);

    expect($context->cancelled())->toBeFalse()
        ->and(utilityMail($this->mail)[0])->toBe([1, ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['content' => [['type' => 'text', 'text' => 'said']]]]]);
});
