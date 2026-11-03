<?php

use App\Mcp\Prompts\GreetPrompt;
use Fabricate\Contracts\Mechanism\Attributes\PromptCall;
use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Mechanism\MessageDecoder;
use Fabricate\Mechanism\Prompt;
use Fabricate\Schema\V20250618;
use Fabricate\Server\MCPServer;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

#[PromptCall('conversation', 'A user line and an assistant reply.')]
class ConversationPrompt extends Prompt
{
    public function __construct(public readonly string $topic) {}

    public function handle(string $protocol_version): array
    {
        return [
            "Tell me about {$this->topic}.",
            new V20250618\Capabilities\Prompts\PromptMessage(Role::ASSISTANT, new V20250618\Concerns\TextContent("Here is what I know about {$this->topic}.")),
        ];
    }
}

#[PromptCall('broken')]
class BrokenPrompt extends Prompt
{
    public function handle(): int { return 42; }
}

#[PromptCall('typed')]
class TypedPrompt extends Prompt
{
    public function __construct(public readonly int $count) {}
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);
    $panel->registerInstance(PromptRegistry::class, $this->registry = new PromptRegistry($panel));

    foreach([GreetPrompt::class, ConversationPrompt::class, BrokenPrompt::class] as $prompt) $this->registry->register($prompt);

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

function promptServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $prompts = true;

        public function prompts(mixed ...$params): array { return ['greet', 'conversation', 'broken']; }
    };
}

function sendPrompts(string $version, string $method, array $params = []): array
{
    if($version === '2026-07-28') $params['_meta'] = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => (object) []];

    $wire = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method] + ($params === [] ? [] : ['params' => $params]));

    return json_decode(new MCPServer(promptServer(), $version)->receiveMessage(MessageDecoder::fromWire($wire))->toJson(), true);
}

it('lists prompts with their arguments from the constructor.', function (string $version) {
    expect(sendPrompts($version, 'prompts/list')['result']['prompts'][0])->toBe([
        'name' => 'greet',
        'description' => 'Ask for a greeting in the given tone.',
        'arguments' => [['name' => 'name', 'required' => true], ['name' => 'tone', 'required' => false]],
    ]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('fills a prompt and returns its text as a user message.', function (string $version) {
    $result = sendPrompts($version, 'prompts/get', ['name' => 'greet', 'arguments' => ['name' => 'Angel', 'tone' => 'pirate']])['result'];

    expect($result['description'])->toBe('Ask for a greeting in the given tone.')
        ->and($result['messages'])->toBe([['role' => 'user', 'content' => ['type' => 'text', 'text' => 'Write a pirate greeting for Angel.']]]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']);

it('passes the version\'s own messages through alongside strings.', function () {
    expect(sendPrompts('2025-06-18', 'prompts/get', ['name' => 'conversation', 'arguments' => ['topic' => 'Saturn']])['result']['messages'])->toBe([
        ['role' => 'user', 'content' => ['type' => 'text', 'text' => 'Tell me about Saturn.']],
        ['role' => 'assistant', 'content' => ['type' => 'text', 'text' => 'Here is what I know about Saturn.']],
    ]);
});

it('refuses an unknown prompt or bad arguments with -32602.', function (array $params) {
    expect(sendPrompts('2025-11-25', 'prompts/get', $params)['error']['code'])->toBe(-32602);
})->with([
    'unknown prompt' => [['name' => 'farewell']],
    'missing argument' => [['name' => 'greet', 'arguments' => ['tone' => 'formal']]],
    'unknown argument' => [['name' => 'greet', 'arguments' => ['name' => 'Angel', 'mood' => 'sad']]],
]);

it('fails loudly when handle() returns something that is not a message.', function () {
    expect(sendPrompts('2025-06-18', 'prompts/get', ['name' => 'broken'])['error']['code'])->toBe(-32603);
});

it('refuses to register a prompt whose arguments are not strings.', function () {
    expect(fn() => $this->registry->register(TypedPrompt::class))->toThrow(InvalidArgumentException::class);
});

it('names why the arguments were refused, without server paths.', function () {
    expect(sendPrompts('2025-06-18', 'prompts/get', ['name' => 'greet'])['error']['message'])
        ->toBe('Invalid arguments for prompt [greet]: Too few arguments to function App\\Mcp\\Prompts\\GreetPrompt::__construct(), 0 passed and at least 1 expected');
});
