<?php

use Fabricate\Contracts\Mechanism\Attributes\ResourceCall;
use Fabricate\Contracts\Mechanism\Attributes\ResourceTemplateCall;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\CompletionResult;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Resource;
use Fabricate\Mechanism\ResourceTemplate;
use Fabricate\Server\MCPServer;
use Fabricate\Server\ResourceRegistry;
use Fabricate\Server\ResourceTemplateRegistry;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Signals\SignalDispatcher;
use Voyager\Vessel\ControlPanel;

#[ResourceCall('memo://today', 'today', 'Today\'s memo.', 'text/plain')]
class TodayResource extends Resource
{
    public function read(): string { return 'Ship resources.'; }
}

#[ResourceTemplateCall('memo://notes/{topic}', 'notes', 'A note on a topic.', 'text/markdown')]
class NotesTemplate extends ResourceTemplate
{
    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        return new CompletionResult(array_values(array_filter(['saturn', 'sun', 'moon'], fn(string $topic) => str_starts_with($topic, $value))));
    }

    public function read(string $uri, string $topic): string { return "# {$topic}"; }
}

#[ResourceCall('memo://slow')]
class SlowResource extends Resource
{
    protected bool $async = true;

    public function read(): ?string
    {
        if(! $this->isAsync()) return 'read in line';

        // The test's loop runs nothing, so the async work finishes as soon as it starts.
        $this->finish('read later');

        return null;
    }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance('signals', new SignalDispatcher($panel));
    $panel->registerInstance(ResourceRegistry::class, $resources = new ResourceRegistry($panel));
    $panel->registerInstance(ResourceTemplateRegistry::class, $templates = new ResourceTemplateRegistry($panel));
    $panel->registerInstance(\Fabricate\Server\PromptRegistry::class, new \Fabricate\Server\PromptRegistry($panel));

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    $resources->register(TodayResource::class);
    $resources->register(SlowResource::class);
    $templates->register(NotesTemplate::class);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function resourceServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $resources = ['subscribe' => true, 'listChanged' => true];
        protected bool $completions = true;

        public function resources(mixed ...$params): array { return ['memo://today', 'memo://notes/{topic}', 'memo://slow']; }
    };
}

function sendResources(MCPServer $server, string $method, array $params = []): ?array
{
    if($server->active_protocol_version === '2026-07-28') $params['_meta'] = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];

    $reply = $server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method] + ($params === [] ? [] : ['params' => $params]))));

    return is_null($reply) ? null : json_decode($reply->toJson(), true);
}

it('lists fixed resources and templates apart.', function (string $version) {
    $server = new MCPServer(resourceServer(), $version);

    expect(sendResources($server, 'resources/list')['result']['resources'][0])->toEqual(['uri' => 'memo://today', 'name' => 'today', 'description' => 'Today\'s memo.', 'mimeType' => 'text/plain'])
        ->and(sendResources($server, 'resources/templates/list')['result']['resourceTemplates'])->toEqual([['uriTemplate' => 'memo://notes/{topic}', 'name' => 'notes', 'description' => 'A note on a topic.', 'mimeType' => 'text/markdown']]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('reads a fixed resource, and a template with its variables filled from the URI.', function (string $version) {
    $server = new MCPServer(resourceServer(), $version);

    expect(sendResources($server, 'resources/read', ['uri' => 'memo://today'])['result']['contents'])->toBe([['uri' => 'memo://today', 'mimeType' => 'text/plain', 'text' => 'Ship resources.']])
        ->and(sendResources($server, 'resources/read', ['uri' => 'memo://notes/saturn'])['result']['contents'])->toBe([['uri' => 'memo://notes/saturn', 'mimeType' => 'text/markdown', 'text' => '# saturn']]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('refuses a URI no resource reads with the code its version names.', function (string $version, int $code) {
    expect(sendResources(new MCPServer(resourceServer(), $version), 'resources/read', ['uri' => 'memo://nowhere'])['error'])
        ->toBe(['code' => $code, 'message' => 'Resource not found: [memo://nowhere].', 'data' => ['uri' => 'memo://nowhere']]);
})->with([
    ['2024-11-05', -32002],
    ['2025-03-26', -32002],
    ['2025-06-18', -32002],
    ['2025-11-25', -32002],
    ['2026-07-28', -32602],
]);

it('answers an async read by mail with the request id.', function () {
    $server = new MCPServer(resourceServer(), '2025-06-18');

    expect(sendResources($server, 'resources/read', ['uri' => 'memo://slow']))->toBeNull()
        ->and($this->mail)->toHaveCount(1)
        ->and($this->mail[0])->toBeInstanceOf(OutboundMessage::class)
        ->and($this->mail[0]->session_id)->toBe($server->session_id)
        ->and(json_decode($this->mail[0]->message()->toJson(), true))->toBe(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['contents' => [['uri' => 'memo://slow', 'text' => 'read later']]]]);
});

it('sends resources/updated only for URIs the session subscribed to.', function () {
    $server = new MCPServer(resourceServer(), '2025-06-18');

    expect(sendResources($server, 'resources/subscribe', ['uri' => 'memo://notes/saturn'])['result'])->toBe([]);

    NotesTemplate::updated('memo://notes/saturn');
    NotesTemplate::updated('memo://notes/moon');
    TodayResource::updated();

    expect($this->mail)->toHaveCount(1)
        ->and($this->mail[0]->message()->toArray())->toBe(['jsonrpc' => '2.0', 'method' => 'notifications/resources/updated', 'params' => ['uri' => 'memo://notes/saturn']]);

    sendResources($server, 'resources/unsubscribe', ['uri' => 'memo://notes/saturn']);
    NotesTemplate::updated('memo://notes/saturn');

    expect($this->mail)->toHaveCount(1);
});

it('refuses to subscribe to a URI no resource reads.', function () {
    expect(sendResources(new MCPServer(resourceServer(), '2025-06-18'), 'resources/subscribe', ['uri' => 'memo://nowhere'])['error']['code'])->toBe(-32002);
});

it('sends resources/list_changed to sessions whose server declared listChanged, and only the named one when a session is given.', function () {
    $first = new MCPServer(resourceServer(), '2025-11-25');
    $second = new MCPServer(resourceServer(), '2025-06-18');

    TodayResource::listChanged();
    TodayResource::listChanged($second->session_id);

    expect(array_map(fn(OutboundMessage $mail) => [$mail->session_id, $mail->message()->toArray()['method']], $this->mail->getArrayCopy()))->toBe([
        [$first->session_id, 'notifications/resources/list_changed'],
        [$second->session_id, 'notifications/resources/list_changed'],
        [$second->session_id, 'notifications/resources/list_changed'],
    ]);
});

it('stops reaching a session once it closes, with one listener per signal however many sessions open.', function () {
    $first = new MCPServer(resourceServer(), '2025-06-18');
    $second = new MCPServer(resourceServer(), '2025-06-18');
    new MCPServer(resourceServer(), '2025-06-18');

    $first->close();
    TodayResource::listChanged();

    expect(count(app('signals')->getListeners(\Fabricate\Mechanism\Signals\ListChanged::class)))->toBe(1)
        ->and($this->mail)->toHaveCount(2)
        ->and($this->mail[0]->session_id)->toBe($second->session_id);
});

it('completes a template variable from the template.', function () {
    $completion = sendResources(new MCPServer(resourceServer(), '2025-06-18'), 'completion/complete', ['ref' => ['type' => 'ref/resource', 'uri' => 'memo://notes/{topic}'], 'argument' => ['name' => 'topic', 'value' => 's']]);

    expect($completion['result']['completion'])->toBe(['values' => ['saturn', 'sun']]);
});

it('refuses completion for an unknown template or variable.', function (array $ref, string $argument) {
    expect(sendResources(new MCPServer(resourceServer(), '2025-06-18'), 'completion/complete', ['ref' => $ref, 'argument' => ['name' => $argument, 'value' => '']])['error']['code'])->toBe(-32602);
})->with([
    'unknown template' => [['type' => 'ref/resource', 'uri' => 'memo://elsewhere/{x}'], 'x'],
    'unknown variable' => [['type' => 'ref/resource', 'uri' => 'memo://notes/{topic}'], 'mood'],
]);
