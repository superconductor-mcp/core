<?php

use Fabricate\Contracts\Mechanism\Attributes\Argument;
use Fabricate\Contracts\Mechanism\Attributes\Icon;
use Fabricate\Contracts\Mechanism\Attributes\PromptCall;
use Fabricate\Contracts\Mechanism\Attributes\ResourceAnnotations;
use Fabricate\Contracts\Mechanism\Attributes\ResourceCall;
use Fabricate\Contracts\Mechanism\Attributes\ResourceTemplateCall;
use Fabricate\Contracts\Mechanism\Attributes\ServerConfig;
use Fabricate\Contracts\Mechanism\Attributes\ToolAnnotations;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\IconTheme;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Prompt;
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

#[ToolCall('gauge', 'Measures a value.', 'Gauge')]
#[ToolAnnotations(title: 'Gauge it', readOnlyHint: true, openWorldHint: false)]
#[Icon('https://example.com/gauge.png', 'image/png', ['48x48'], IconTheme::LIGHT)]
class GaugeTool extends Tool
{
    public function __construct(#[Argument('What to measure.', 'Subject')] public readonly string $subject) {}

    public static function outputSchema(): ?array
    {
        return ['properties' => ['subject' => ['type' => 'string'], 'reading' => ['type' => 'number']], 'required' => ['subject', 'reading']];
    }

    public function handle(): array { return ['subject' => $this->subject, 'reading' => 4.2]; }
}

#[ToolCall('picture')]
class PictureTool extends Tool
{
    public function handle(string $protocol_version): array
    {
        $image = 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\Concerns\\ImageContent';

        return ['a caption', new $image('aGk=', 'image/png')];
    }
}

#[PromptCall('brief', 'Writes a brief.', 'Brief')]
#[Icon('https://example.com/brief.svg', 'image/svg+xml', ['any'])]
class BriefPrompt extends Prompt
{
    public function __construct(#[Argument('What the brief is about.', 'Topic')] public readonly string $topic) {}

    public function handle(): string { return "Brief me on {$this->topic}."; }
}

#[ResourceCall('meta://report', 'report', 'The report.', 'text/plain', 'Report', 2048)]
#[ResourceAnnotations([Role::USER], 0.5, '2026-11-02T00:00:00Z')]
#[Icon('https://example.com/report.png')]
class ReportResource extends Resource
{
    public function read(): string { return 'all good'; }
}

#[ResourceTemplateCall('meta://day/{date}', 'day', 'A day.', 'text/plain', 'Day')]
#[ResourceAnnotations([Role::ASSISTANT], 1)]
class DayTemplate extends ResourceTemplate
{
    public function read(string $uri, string $date): string { return $date; }
}

#[ServerConfig('meta', 'Metadata test server.', '1.0.0', 'Meta', 'https://example.com', 'Ask for a gauge reading.')]
#[Icon('https://example.com/server.png', 'image/png')]
class MetaServer extends ServerConfiguration
{
    protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25'];
    protected bool|array $tools = true;
    protected bool|array $prompts = true;
    protected bool|array $resources = true;

    public function tools(mixed ...$params): array { return ['gauge', 'picture']; }
    public function prompts(mixed ...$params): array { return ['brief']; }
    public function resources(mixed ...$params): array { return ['meta://report', 'meta://day/{date}']; }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(ToolRegistry::class, $tools = new ToolRegistry($panel));
    $panel->registerInstance(PromptRegistry::class, $prompts = new PromptRegistry($panel));
    $panel->registerInstance(ResourceRegistry::class, $resources = new ResourceRegistry($panel));
    $panel->registerInstance(ResourceTemplateRegistry::class, $templates = new ResourceTemplateRegistry($panel));
    $panel->registerInstance(Loop::class, $this->createMock(Loop::class));

    $tools->register(GaugeTool::class);
    $tools->register(PictureTool::class);
    $prompts->register(BriefPrompt::class);
    $resources->register(ReportResource::class);
    $templates->register(DayTemplate::class);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function metaRpc(string $version, string $method, array $params = []): array
{
    $reply = new MCPServer(new MetaServer(new ServerCapabilitiesFactory()), $version)
        ->receiveMessage(MessageDecoder::fromWire(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method] + ($params === [] ? [] : ['params' => $params]))));

    return json_decode($reply->toJson(), true);
}

it('defines a tool with what each version has room for.', function (string $version, array $expected) {
    expect(metaRpc($version, 'tools/list')['result']['tools'][0])->toEqual($expected);
})->with(function () {
    $input = ['type' => 'object', 'properties' => ['subject' => ['type' => 'string', 'title' => 'Subject', 'description' => 'What to measure.']], 'required' => ['subject']];
    $output = ['type' => 'object', 'properties' => ['subject' => ['type' => 'string'], 'reading' => ['type' => 'number']], 'required' => ['subject', 'reading']];
    $hints = ['title' => 'Gauge it', 'readOnlyHint' => true, 'openWorldHint' => false];
    $base = ['name' => 'gauge', 'description' => 'Measures a value.', 'inputSchema' => $input];

    return [
        ['2024-11-05', $base],
        ['2025-03-26', $base + ['annotations' => $hints]],
        ['2025-06-18', $base + ['annotations' => $hints, 'outputSchema' => $output, 'title' => 'Gauge']],
        ['2025-11-25', $base + ['annotations' => $hints, 'outputSchema' => $output, 'title' => 'Gauge', 'icons' => [['src' => 'https://example.com/gauge.png', 'mimeType' => 'image/png', 'sizes' => ['48x48'], 'theme' => 'light']]]],
    ];
});

it('answers a tool with an output schema as structuredContent from 2025-06-18, and as its JSON in every version.', function (string $version, bool $structured) {
    $result = metaRpc($version, 'tools/call', ['name' => 'gauge', 'arguments' => ['subject' => 'pressure']])['result'];

    expect($result['content'])->toBe([['type' => 'text', 'text' => '{"subject":"pressure","reading":4.2}']])
        ->and($result['structuredContent'] ?? null)->toBe($structured ? ['subject' => 'pressure', 'reading' => 4.2] : null);
})->with([['2024-11-05', false], ['2025-03-26', false], ['2025-06-18', true], ['2025-11-25', true]]);

it('passes a tool\'s own content objects through beside its text.', function (string $version) {
    expect(metaRpc($version, 'tools/call', ['name' => 'picture'])['result']['content'])
        ->toBe([['type' => 'text', 'text' => 'a caption'], ['type' => 'image', 'data' => 'aGk=', 'mimeType' => 'image/png']]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('defines a prompt with its arguments\' descriptions, and a title and icons where the version has them.', function (string $version, array $expected) {
    expect(metaRpc($version, 'prompts/list')['result']['prompts'][0])->toEqual($expected);
})->with([
    ['2024-11-05', ['name' => 'brief', 'description' => 'Writes a brief.', 'arguments' => [['name' => 'topic', 'description' => 'What the brief is about.', 'required' => true]]]],
    ['2025-03-26', ['name' => 'brief', 'description' => 'Writes a brief.', 'arguments' => [['name' => 'topic', 'description' => 'What the brief is about.', 'required' => true]]]],
    ['2025-06-18', ['name' => 'brief', 'title' => 'Brief', 'description' => 'Writes a brief.', 'arguments' => [['name' => 'topic', 'title' => 'Topic', 'description' => 'What the brief is about.', 'required' => true]]]],
    ['2025-11-25', ['name' => 'brief', 'title' => 'Brief', 'description' => 'Writes a brief.', 'arguments' => [['name' => 'topic', 'title' => 'Topic', 'description' => 'What the brief is about.', 'required' => true]], 'icons' => [['src' => 'https://example.com/brief.svg', 'mimeType' => 'image/svg+xml', 'sizes' => ['any']]]]],
]);

it('defines resources and templates with their size, annotations, title and icons where the version has them.', function (string $version, array $resource, array $template) {
    expect(metaRpc($version, 'resources/list')['result']['resources'][0])->toEqual($resource)
        ->and(metaRpc($version, 'resources/templates/list')['result']['resourceTemplates'][0])->toEqual($template);
})->with(function () {
    $resource = ['uri' => 'meta://report', 'name' => 'report', 'description' => 'The report.', 'mimeType' => 'text/plain', 'size' => 2048];
    $template = ['uriTemplate' => 'meta://day/{date}', 'name' => 'day', 'description' => 'A day.', 'mimeType' => 'text/plain'];

    return [
        ['2024-11-05', $resource + ['annotations' => ['audience' => ['user'], 'priority' => 0.5]], $template + ['annotations' => ['audience' => ['assistant'], 'priority' => 1]]],
        ['2025-03-26', $resource + ['annotations' => ['audience' => ['user'], 'priority' => 0.5]], $template + ['annotations' => ['audience' => ['assistant'], 'priority' => 1]]],
        ['2025-06-18', $resource + ['annotations' => ['audience' => ['user'], 'priority' => 0.5], 'title' => 'Report'], $template + ['annotations' => ['audience' => ['assistant'], 'priority' => 1], 'title' => 'Day']],
        ['2025-11-25', $resource + ['annotations' => ['audience' => ['user'], 'priority' => 0.5, 'lastModified' => '2026-11-02T00:00:00Z'], 'title' => 'Report', 'icons' => [['src' => 'https://example.com/report.png']]], $template + ['annotations' => ['audience' => ['assistant'], 'priority' => 1], 'title' => 'Day']],
    ];
});

it('answers initialize with the server\'s instructions, and its icons from 2025-11-25.', function (string $version, ?array $icons) {
    $result = new MetaServer(new ServerCapabilitiesFactory())->initializeResult($version)->toArray();

    expect($result['instructions'])->toBe('Ask for a gauge reading.')
        ->and($result['serverInfo']['icons'] ?? null)->toBe($icons);
})->with([
    ['2024-11-05', null],
    ['2025-06-18', null],
    ['2025-11-25', [['src' => 'https://example.com/server.png', 'mimeType' => 'image/png']]],
]);

#[ToolCall('bad name!')]
class BadlyNamedTool extends Tool
{
    public function handle(): string { return ''; }
}

#[ToolCall('a.b-c_D9')]
class WellNamedTool extends Tool
{
    public function handle(): string { return ''; }
}

it('registers only tools named by the MCP tool name rules.', function () {
    $tools = app(ToolRegistry::class);
    $tools->register(WellNamedTool::class);

    expect($tools->has('a.b-c_D9'))->toBeTrue()
        ->and(fn() => $tools->register(BadlyNamedTool::class))->toThrow(InvalidArgumentException::class);
});
