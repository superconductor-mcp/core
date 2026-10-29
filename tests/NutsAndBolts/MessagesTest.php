<?php

use Fabricate\Contracts\NutsAndBolts\LogLevel;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Ping\PingResponse;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolRequest;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsRequest;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsResponse;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptResponse;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializedNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;

it('sends requests as JSON-RPC lines with empty maps as objects', function () {
    expect((new InitializeRequest(0, '2024-11-05', [], ['name' => 'x', 'version' => '1']))->toJson())
        ->toBe('{"jsonrpc":"2.0","id":0,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"x","version":"1"}}}');

    expect((new CallToolRequest(1, 'countdown'))->toJson())->toContain('"arguments":{}');
    expect((new GetPromptRequest(2, 'greet'))->toJson())->toContain('"arguments":{}');
    expect((new ListToolsRequest(3))->toJson())->toBe('{"jsonrpc":"2.0","id":3,"method":"tools\/list"}');
    expect((new ListToolsRequest(4, 'c2'))->toJson())->toContain('"params":{"cursor":"c2"}');
    expect((new PingRequest(5))->toJson())->toBe('{"jsonrpc":"2.0","id":5,"method":"ping"}');
    expect((new InitializedNotification())->toJson())->toBe('{"jsonrpc":"2.0","method":"notifications\/initialized"}');
});

it('builds typed responses from a received reply', function () {
    $init = InitializeResponse::fromReceived(new ReceivedResponse(0, ['protocolVersion' => '2024-11-05', 'capabilities' => ['tools' => []], 'serverInfo' => ['name' => 's', 'version' => '2']]));
    expect($init->protocol_version)->toBe('2024-11-05')->and($init->server_info['name'])->toBe('s');

    $tools = ListToolsResponse::fromReceived(new ReceivedResponse(1, ['tools' => [['name' => 'a', 'inputSchema' => ['type' => 'object']]], 'nextCursor' => 'c2']));
    expect($tools->tools)->toHaveCount(1)->and($tools->next_cursor)->toBe('c2');

    $call = CallToolResponse::fromReceived(new ReceivedResponse(2, ['content' => [['type' => 'text', 'text' => 'hi']], 'isError' => true]));
    expect($call->is_error)->toBeTrue()->and($call->toJson())->toContain('"isError":true');

    $read = ReadResourceResponse::fromReceived(new ReceivedResponse(3, ['contents' => [['uri' => 'x://y', 'mimeType' => 'text/plain', 'text' => 'body']]]));
    expect($read->toJson())->toContain('"text":"body"');

    $prompt = GetPromptResponse::fromReceived(new ReceivedResponse(4, ['description' => 'd', 'messages' => [['role' => 'user', 'content' => ['type' => 'text', 'text' => 'q']]]]));
    expect($prompt->description)->toBe('d')->and($prompt->toJson())->toContain('"role":"user"');

    expect(PingResponse::fromReceived(new ReceivedResponse(5, []))->id)->toBe(5);
});

it('refuses a reply whose result has the wrong shape', function () {
    expect(fn () => ListToolsResponse::fromReceived(new ReceivedResponse(1, ['tools' => 'nope'])))->toThrow(UnexpectedValueException::class);
    expect(fn () => InitializeResponse::fromReceived(new ReceivedResponse(1, ['protocolVersion' => 5])))->toThrow(UnexpectedValueException::class);
    expect(fn () => CallToolResponse::fromReceived(new ReceivedResponse(1, ['content' => [], 'isError' => 'yes'])))->toThrow(UnexpectedValueException::class);
});

it('builds server notifications from their params and refuses bad ones', function () {
    expect(LoggingMessageNotification::generate(['level' => 'info', 'data' => 'x']))->toBeInstanceOf(LoggingMessageNotification::class);
    expect(LoggingMessageNotification::generate(['level' => 'loud', 'data' => 'x']))->toBeNull();
    expect(LoggingMessageNotification::generate(['level' => 'info']))->toBeNull();
    expect(LogLevel::Error->atLeast(LogLevel::Warning))->toBeTrue();
});
