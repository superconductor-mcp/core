<?php

use Fabricate\NutsAndBolts\LineDecoder;
use Fabricate\NutsAndBolts\Messages\Errors\ParseError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Errors\UnknownNotification;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolRequest;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;
use Fabricate\NutsAndBolts\Messages\Progress\ProgressNotification;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializedNotification;
use Fabricate\NutsAndBolts\Messages\Tools\ToolListChangedNotification;
use Fabricate\NutsAndBolts\Messages\Logging\LoggingMessageNotification;
use Fabricate\NutsAndBolts\Messages\Resources\ResourceUpdatedNotification;

function line(array $message): string
{
    return json_encode(['jsonrpc' => '2.0', ...$message]);
}

it('decodes a request into its typed class', function () {
    $request = LineDecoder::decodeString(line(['id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2024-11-05', 'capabilities' => [], 'clientInfo' => ['name' => 'x', 'version' => '1']]]));

    expect($request)->toBeInstanceOf(InitializeRequest::class)
        ->and($request->id)->toBe(1)
        ->and($request->protocol_version)->toBe('2024-11-05');

    expect(LineDecoder::decodeString(line(['id' => 'a', 'method' => 'ping'])))->toBeInstanceOf(PingRequest::class);
    expect(LineDecoder::decodeString(line(['id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'echo']])))->toBeInstanceOf(CallToolRequest::class);
});

it('decodes the notifications either side sends', function () {
    expect(LineDecoder::decodeString(line(['method' => 'notifications/initialized'])))->toBeInstanceOf(InitializedNotification::class);
    expect(LineDecoder::decodeString(line(['method' => 'notifications/tools/list_changed'])))->toBeInstanceOf(ToolListChangedNotification::class);

    $updated = LineDecoder::decodeString(line(['method' => 'notifications/resources/updated', 'params' => ['uri' => 'x://y']]));
    expect($updated)->toBeInstanceOf(ResourceUpdatedNotification::class)->and($updated->uri)->toBe('x://y');

    $log = LineDecoder::decodeString(line(['method' => 'notifications/message', 'params' => ['level' => 'warning', 'data' => ['a' => 1], 'logger' => 'db']]));
    expect($log)->toBeInstanceOf(LoggingMessageNotification::class)->and($log->level->value)->toBe('warning')->and($log->logger)->toBe('db');

    $progress = LineDecoder::decodeString(line(['method' => 'notifications/progress', 'params' => ['progressToken' => 'p', 'progress' => 2, 'total' => 5]]));
    expect($progress)->toBeInstanceOf(ProgressNotification::class)->and($progress->total)->toBe(5);
});

it('turns a reply into a ReceivedResponse carrying exactly one of result or error', function () {
    $ok = LineDecoder::decodeString(line(['id' => 3, 'result' => ['tools' => []]]));
    expect($ok)->toBeInstanceOf(ReceivedResponse::class)->and($ok->result)->toBe(['tools' => []])->and($ok->error)->toBeNull();

    $failed = LineDecoder::decodeString(line(['id' => 3, 'error' => ['code' => -32601, 'message' => 'nope']]));
    expect($failed)->toBeInstanceOf(ReceivedResponse::class)->and($failed->error['code'])->toBe(-32601);

    expect(LineDecoder::decodeString(line(['id' => 3, 'result' => [], 'error' => ['code' => 1, 'message' => 'x']])))->toBeInstanceOf(InvalidRequest::class);
});

it('answers garbage with a parse error and no id', function () {
    $error = LineDecoder::decodeString('not json');

    expect($error)->toBeInstanceOf(ParseError::class)->and($error->id)->toBeNull()->and($error->replies())->toBeTrue();
});

it('marks an unknown notification as something never answered', function () {
    $error = LineDecoder::decodeString(line(['method' => 'notifications/whatever']));

    expect($error)->toBeInstanceOf(UnknownNotification::class)->and($error->replies())->toBeFalse();
});

it('keeps the request id on a parameter error so the client can match it', function () {
    $error = LineDecoder::decodeString(line(['id' => 7, 'method' => 'logging/setLevel', 'params' => ['level' => 'loud']]));

    expect($error)->toBeInstanceOf(InvalidParams::class)->and($error->id)->toBe(7)->and($error->code)->toBe(-32602);
});

it('refuses an unknown method with its id and a wrong jsonrpc with a request error', function () {
    $unknown = LineDecoder::decodeString(line(['id' => 8, 'method' => 'nope/nothing']));
    expect($unknown)->toBeInstanceOf(MethodNotFound::class)->and($unknown->id)->toBe(8);

    expect(LineDecoder::decodeString(json_encode(['jsonrpc' => '1.0', 'id' => 9, 'method' => 'ping'])))->toBeInstanceOf(InvalidRequest::class);
});

it('decodes a JSON-RPC batch into one message per element', function () {
    $messages = LineDecoder::decode(json_encode([
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'],
        ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
        ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'nope'],
        'not an object',
    ]));

    expect($messages)->toHaveCount(4)
        ->and($messages[0])->toBeInstanceOf(PingRequest::class)
        ->and($messages[1])->toBeInstanceOf(InitializedNotification::class)
        ->and($messages[2])->toBeInstanceOf(MethodNotFound::class)
        ->and($messages[2]->id)->toBe(2)
        ->and($messages[3])->toBeInstanceOf(InvalidRequest::class);

    // a single object is a batch of one; an empty array is invalid per JSON-RPC
    expect(LineDecoder::decode(line(['id' => 'a', 'method' => 'ping'])))->toHaveCount(1)->sequence(fn ($m) => $m->toBeInstanceOf(PingRequest::class));
    expect(LineDecoder::decode('[]'))->toHaveCount(1)->sequence(fn ($m) => $m->toBeInstanceOf(InvalidRequest::class));
    expect(LineDecoder::decode('{'))->toHaveCount(1)->sequence(fn ($m) => $m->toBeInstanceOf(ParseError::class));
});
