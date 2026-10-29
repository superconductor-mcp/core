<?php

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\Mechanism\ProcedureRouter;
use Fabricate\Tests\Fixtures\BenchServer;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Ping\PingResponse;
use Fabricate\NutsAndBolts\Messages\Errors\ToolCallError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolRequest;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsRequest;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;

function bench(array $config = []): array
{
    bareApp($config);
    $bag = new MCPServerBag();
    $bag->server_configuration = new BenchServer();

    return [new ProcedureRouter(), $bag];
}

it('answers initialize with the version the client proposed when it is supported', function () {
    [$router, $bag] = bench();
    $response = $router->dispatch(new InitializeRequest(0, '2024-11-05', [], ['name' => 'c', 'version' => '1']), $bag);

    expect($response)->toBeInstanceOf(InitializeResponse::class)
        ->and($response->protocol_version)->toBe('2024-11-05')
        ->and($response->capabilities['tools']['listChanged'])->toBeTrue()
        ->and($bag->client_info['name'])->toBe('c');
});

it('echoes 2025-11-25 to a client proposing it, and each older version it still speaks', function (string $version) {
    [$router, $bag] = bench();
    expect($router->dispatch(new InitializeRequest(0, $version, [], ['name' => 'c', 'version' => '1']), $bag)->protocol_version)->toBe($version);
})->with(['2025-11-25', '2025-06-18', '2025-03-26', '2024-11-05']);

it('offers its latest version to a client proposing one it does not speak', function () {
    [$router, $bag] = bench();
    expect($router->dispatch(new InitializeRequest(0, '2030-01-01', [], ['name' => 'c', 'version' => '1']), $bag)->protocol_version)->toBe('2025-11-25');
});

it('answers ping and refuses an unrouted method', function () {
    [$router, $bag] = bench();
    expect($router->dispatch(new PingRequest(1), $bag))->toBeInstanceOf(PingResponse::class);

    $unrouted = new readonly class(2) extends MCPRequest {
        public function __construct(int $id) { parent::__construct($id, 'nope/nothing'); }
        public static function generate(mixed $id, mixed $method, mixed $params, mixed $jsonrpc = '2.0'): never { throw new LogicException(); }
    };
    expect($router->dispatch($unrouted, $bag))->toBeInstanceOf(MethodNotFound::class);
});

it('lists tools one page at a time', function () {
    [$router, $bag] = bench(['pagination' => ['page_size' => 1]]);

    $first = $router->dispatch(new ListToolsRequest(1), $bag);
    expect($first)->toBeInstanceOf(ListToolsResponse::class)->and($first->tools)->toHaveCount(1)->and($first->tools[0]['name'])->toBe('echo')->and($first->next_cursor)->not->toBeNull();

    $second = $router->dispatch(new ListToolsRequest(2, $first->next_cursor), $bag);
    expect($second->tools[0]['name'])->toBe('boom')->and($second->next_cursor)->toBeNull();

    expect($router->dispatch(new ListToolsRequest(3, 'garbage'), $bag))->toBeInstanceOf(InvalidParams::class);
});

it('calls a tool with its named arguments', function () {
    [$router, $bag] = bench();
    $response = $router->dispatch(new CallToolRequest(1, 'echo', ['text' => 'hi']), $bag);

    expect($response)->toBeInstanceOf(CallToolResponse::class)->and($response->is_error)->toBeFalse()->and($response->toJson())->toContain('"text":"hi"');
});

it('reports a tool that throws inside its result, not as a protocol error', function () {
    [$router, $bag] = bench();
    $response = $router->dispatch(new CallToolRequest(1, 'boom'), $bag);

    expect($response)->toBeInstanceOf(CallToolResponse::class)->and($response->is_error)->toBeTrue()->and($response->toJson())->toContain('kaboom');
});

it('refuses an unknown tool and a missing required argument as protocol errors', function () {
    [$router, $bag] = bench();

    expect($router->dispatch(new CallToolRequest(1, 'nope'), $bag))->toBeInstanceOf(ToolCallError::class);

    $missing = $router->dispatch(new CallToolRequest(2, 'echo'), $bag);
    expect($missing)->toBeInstanceOf(InvalidParams::class)->and($missing->error['data'])->toContain('text');
});

// 2025-11-25 (SEP-1303)
it('reports bad tool arguments in the result from 2025-11-25, and as InvalidParams before', function (string $version, bool $in_result) {
    [$router, $bag] = bench();
    $bag->protocol_version = $version;

    $problems = [1 => 'Missing required argument: text', 2 => 'Unknown argument: extra', 4 => 'Invalid argument text: must be a string.'];

    foreach ([new CallToolRequest(1, 'echo'), new CallToolRequest(2, 'echo', ['text' => 'x', 'extra' => 1]), new CallToolRequest(4, 'echo', ['text' => 5])] as $request) {
        $response = $router->dispatch($request, $bag);

        if ($in_result) {
            expect($response)->toBeInstanceOf(CallToolResponse::class)
                ->and($response->is_error)->toBeTrue()
                ->and($response->content[0]->value)->toBe($problems[$request->id]);
        } else {
            expect($response)->toBeInstanceOf(InvalidParams::class);
        }
    }

    // an unknown tool is still a protocol error, whatever the version
    expect($router->dispatch(new CallToolRequest(3, 'nope'), $bag))->toBeInstanceOf(ToolCallError::class);
})->with([['2025-11-25', true], ['2025-06-18', false], ['2025-03-26', false], ['2024-11-05', false]]);

it('still runs a tool whose arguments are fine on 2025-11-25', function () {
    [$router, $bag] = bench();
    $bag->protocol_version = '2025-11-25';
    $response = $router->dispatch(new CallToolRequest(1, 'echo', ['text' => 'hi']), $bag);

    expect($response)->toBeInstanceOf(CallToolResponse::class)->and($response->is_error)->toBeFalse();
});

it('knows which procedures run in a fiber', function () {
    [$router] = bench();

    expect($router->runsAsync(new CallToolRequest(1, 'echo')))->toBeTrue()
        ->and($router->runsAsync(new PingRequest(1)))->toBeFalse()
        ->and($router->runsAsync(new ListToolsRequest(1)))->toBeFalse();
});
