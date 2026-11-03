<?php

use App\Mcp\Prompts\GreetPrompt;
use App\Mcp\Tools\EchoTool;
use App\Mcp\Tools\ShoutTool;
use Fabricate\Contracts\Mechanism\Attributes\ResourceCall;
use Fabricate\Contracts\Mechanism\Attributes\ResourceTemplateCall;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Core\Capabilities\Tools;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Resource;
use Fabricate\Mechanism\ResourceTemplate;
use Fabricate\Mechanism\Tool;
use Fabricate\Server\MCPServer;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\ResourceRegistry;
use Fabricate\Server\ResourceTemplateRegistry;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Fabricate\Server\ToolRegistry;
use Voyager\Config\Repository;
use Voyager\Contracts\IOPools\Loop;
use Voyager\Vessel\ControlPanel;

#[ToolCall('mull', 'Mulls a message over, off the loop.')]
class MullTool extends Tool
{
    protected bool $async = true;

    public static ?MullTool $pending = null;

    public function __construct(public readonly string $message) {}

    public function handle(): ?string
    {
        if(! $this->isAsync()) return "mulled {$this->message}";

        static::$pending = $this;

        return null;
    }

    public function release(): void { $this->finish("mulled {$this->message}"); }
}

#[ResourceCall('cat://menu', 'menu', 'The menu.')]
class MenuResource extends Resource
{
    public function read(): string { return 'soup'; }
}

#[ResourceTemplateCall('cat://dish/{name}', 'dish', 'One dish.')]
class DishTemplate extends ResourceTemplate
{
    public function read(string $name): string { return $name; }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));
    $panel->registerInstance(PromptRegistry::class, $prompts = new PromptRegistry($panel));
    $panel->registerInstance(ResourceRegistry::class, $resources = new ResourceRegistry($panel));
    $panel->registerInstance(ResourceTemplateRegistry::class, $templates = new ResourceTemplateRegistry($panel));

    $this->mail = new ArrayObject();
    $loop = $this->createMock(Loop::class);
    $loop->method('post')->willReturnCallback(fn(object $mail) => $this->mail->append($mail));
    $panel->registerInstance(Loop::class, $loop);

    foreach([Tools\ListToolsTool::class, Tools\SearchToolsTool::class, Tools\ExecuteToolTool::class, Tools\SearchPromptsTool::class, Tools\ListResourcesTool::class,
        Tools\SearchResourcesTool::class, Tools\ListResourceTemplatesTool::class, Tools\SearchResourceTemplatesTool::class, EchoTool::class, ShoutTool::class, MullTool::class] as $tool) $tools->register($tool);
    $prompts->register(GreetPrompt::class);
    $resources->register(MenuResource::class);
    $templates->register(DishTemplate::class);
    MullTool::$pending = null;

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function catalogServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;

        public function tools(mixed ...$params): array
        {
            return ['list_tools', 'search_tools', 'execute_tool', 'search_prompts', 'list_resources', 'search_resources', 'list_resource_templates', 'search_resource_templates', 'echo', 'shout', 'mull'];
        }

        public function prompts(mixed ...$params): array { return ['greet']; }
        public function resources(mixed ...$params): array { return ['cat://menu', 'cat://dish/{name}']; }
    };
}

function useTool(MCPServer $server, string $name, array $arguments = []): ?array
{
    $reply = $server->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => 5, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => (object) $arguments]])));

    return is_null($reply) ? null : json_decode($reply->toJson(), true)['result'];
}

function toolJson(?array $result): array
{
    return json_decode($result['content'][0]['text'], true);
}

it('lists and searches what the server offers.', function () {
    $server = new MCPServer(catalogServer(), '2025-06-18');

    expect(array_column(toolJson(useTool($server, 'list_tools')), 'name'))->toHaveCount(11)
        ->and(array_column(toolJson(useTool($server, 'search_tools', ['query' => 'upper case'])), 'name'))->toBe(['shout'])
        ->and(toolJson(useTool($server, 'search_prompts', ['query' => 'greet'])))->toBe([['name' => 'greet', 'description' => 'Ask for a greeting in the given tone.', 'arguments' => [['name' => 'name', 'required' => true], ['name' => 'tone', 'required' => false]]]])
        ->and(toolJson(useTool($server, 'list_resources')))->toBe([['uri' => 'cat://menu', 'name' => 'menu', 'description' => 'The menu.']])
        ->and(toolJson(useTool($server, 'search_resources', ['query' => 'nothing'])))->toBe([])
        ->and(toolJson(useTool($server, 'list_resource_templates')))->toBe([['uriTemplate' => 'cat://dish/{name}', 'name' => 'dish', 'description' => 'One dish.']])
        ->and(toolJson(useTool($server, 'search_resource_templates', ['query' => 'DISH'])))->toHaveCount(1);
});

it('runs a sync tool through execute_tool, answering by mail.', function () {
    $server = new MCPServer(catalogServer(), '2025-06-18');

    expect(useTool($server, 'execute_tool', ['name' => 'shout', 'arguments' => ['message' => 'hey']]))->toBeNull()
        ->and($this->mail[0]->message()->toArray()['result']['content'])->toBe([['type' => 'text', 'text' => 'HEY']]);
});

it('waits for an async tool through execute_tool without holding the loop.', function () {
    $server = new MCPServer(catalogServer(), '2025-06-18');

    expect(useTool($server, 'execute_tool', ['name' => 'mull', 'arguments' => ['message' => 'it']]))->toBeNull()
        ->and($this->mail)->toHaveCount(0);

    MullTool::$pending->release();

    expect($this->mail[0]->message()->toArray()['result']['content'])->toBe([['type' => 'text', 'text' => 'mulled it']])
        ->and($this->mail[0]->message()->toArray()['id'])->toBe(5);
});

it('reports a tool execute_tool can\'t run as an error result.', function (array $arguments, string $message) {
    useTool(new MCPServer(catalogServer(), '2025-06-18'), 'execute_tool', $arguments);

    expect($this->mail[0]->message()->toArray()['result'])->toBe(['content' => [['type' => 'text', 'text' => $message]], 'isError' => true]);
})->with([
    'unknown' => [['name' => 'nope'], 'Unknown tool: [nope].'],
    'itself' => [['name' => 'execute_tool'], 'Unknown tool: [execute_tool].'],
    'bad arguments' => [['name' => 'echo', 'arguments' => ['loud' => true]], 'Invalid arguments for tool [echo]: Unknown named parameter $loud'],
]);
