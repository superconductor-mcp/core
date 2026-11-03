<?php

use App\Mcp\Prompts\GreetPrompt;
use Fabricate\Contracts\Mechanism\Attributes\PromptCall;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\CompletionResult;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Prompt;
use Fabricate\Server\MCPServer;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

#[PromptCall('city')]
class CityPrompt extends Prompt
{
    public function __construct(public readonly string $country, public readonly string $city) {}

    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        $cities = ['japan' => ['Tokyo', 'Osaka'], 'france' => ['Paris', 'Lyon']][strtolower($context['country'] ?? '')] ?? [];

        return new CompletionResult($argument === 'city' ? $cities : []);
    }

    public function handle(): string { return "Tell me about {$this->city}."; }
}

#[PromptCall('many')]
class ManyPrompt extends Prompt
{
    public function __construct(public readonly string $pick) {}

    public static function complete(string $argument, string $value, array $context = []): CompletionResult
    {
        return new CompletionResult(array_map(fn(int $i) => "v{$i}", range(1, 150)), 150);
    }

    public function handle(): string { return $this->pick; }
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(PromptRegistry::class, $registry = new PromptRegistry($panel));
    $panel->registerInstance(\Fabricate\Server\ResourceTemplateRegistry::class, new \Fabricate\Server\ResourceTemplateRegistry($panel));

    foreach([GreetPrompt::class, CityPrompt::class, ManyPrompt::class] as $prompt) $registry->register($prompt);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function completingServer(bool $completions = true): ServerConfiguration
{
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $prompts = true;
        public bool $offers_completions = true;

        public function completionsConfig(): ?array { return $this->offers_completions ? [] : null; }
        public function prompts(mixed ...$params): array { return ['greet', 'city', 'many']; }
    };
    $server->offers_completions = $completions;

    return $server;
}

function complete(string $version, array $params, ?ServerConfiguration $server = null): array
{
    if($version === '2026-07-28') $params['_meta'] = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];

    $wire = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'completion/complete', 'params' => $params]);

    return json_decode(new MCPServer($server ?? completingServer(), $version)->receiveMessage(MessageDecoder::fromWire($wire))->toJson(), true);
}

function promptRef(string $name, string $argument, string $value): array
{
    return ['ref' => ['type' => 'ref/prompt', 'name' => $name], 'argument' => ['name' => $argument, 'value' => $value]];
}

it('completes a prompt argument from the prompt.', function (string $version) {
    expect(complete($version, promptRef('greet', 'tone', 'f'))['result']['completion'])->toBe(['values' => ['friendly', 'formal']]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('passes the arguments already filled in as context.', function () {
    $params = promptRef('city', 'city', '') + ['context' => ['arguments' => ['country' => 'Japan']]];

    expect(complete('2025-06-18', $params)['result']['completion']['values'])->toBe(['Tokyo', 'Osaka']);
});

it('caps a completion at 100 values and says there are more.', function () {
    $completion = complete('2025-06-18', promptRef('many', 'pick', ''))['result']['completion'];

    expect($completion['values'])->toHaveCount(100)
        ->and($completion['total'])->toBe(150)
        ->and($completion['hasMore'])->toBeTrue();
});

it('refuses an unknown prompt or argument with -32602.', function (array $params) {
    expect(complete('2025-06-18', $params)['error']['code'])->toBe(-32602);
})->with([
    'unknown prompt' => [promptRef('farewell', 'tone', 'f')],
    'unknown argument' => [promptRef('greet', 'mood', 'h')],
]);

it('refuses completion from a server without the completions capability.', function () {
    expect(complete('2025-06-18', promptRef('greet', 'tone', 'f'), completingServer(false))['error']['code'])->toBe(-32601);
});
