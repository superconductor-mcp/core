<?php

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\Decoding\DecodedBatch;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedNotification;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Mechanism\Decoding\InvalidMessage;
use Fabricate\Mechanism\MessageDecoder;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
});

afterEach(fn() => ControlPanel::setInstance(null));

it('decodes a request with its id, method and params.', function () {
    $message = MessageDecoder::fromWire('{"jsonrpc":"2.0","id":7,"method":"tools/call","params":{"name":"echo"}}');

    expect($message)->toBeInstanceOf(DecodedRequest::class)
        ->and($message->id->id)->toBe(7)
        ->and($message->method)->toBe('tools/call')
        ->and($message->params)->toBe(['name' => 'echo']);
});

it('decodes a notification, including one whose method is an empty string.', function () {
    $message = MessageDecoder::fromWire('{"jsonrpc":"2.0","method":""}');

    expect($message)->toBeInstanceOf(DecodedNotification::class)
        ->and($message->method)->toBe('')
        ->and($message->params)->toBeNull();
});

it('decodes a response whose result is null.', function () {
    $message = MessageDecoder::fromWire('{"jsonrpc":"2.0","id":"a","result":null}');

    expect($message)->toBeInstanceOf(DecodedResponse::class)
        ->and($message->id->id)->toBe('a')
        ->and($message->result)->toBeNull();
});

it('decodes an error, with or without an id.', function (string $wire, string|int|null $id) {
    $message = MessageDecoder::fromWire($wire);

    expect($message)->toBeInstanceOf(DecodedError::class)
        ->and($message->id?->id)->toBe($id)
        ->and($message->code)->toBe(-32002)
        ->and($message->message)->toBe('Not found')
        ->and($message->data)->toBe(['uri' => 'file:///x']);
})->with([
    'with id' => ['{"jsonrpc":"2.0","id":3,"error":{"code":-32002,"message":"Not found","data":{"uri":"file:///x"}}}', 3],
    'null id' => ['{"jsonrpc":"2.0","id":null,"error":{"code":-32002,"message":"Not found","data":{"uri":"file:///x"}}}', null],
]);

it('decodes an already decoded body the same way as raw text.', function () {
    $wire = '{"jsonrpc":"2.0","id":1,"method":"ping"}';

    expect(MessageDecoder::fromBody(json_decode($wire, true)))->toEqual(MessageDecoder::fromWire($wire));
});

it('rejects a malformed payload with the right code and the id it can recover.', function (string $wire, RPCErrorCode $code, string|int|null $id) {
    $message = MessageDecoder::fromWire($wire);

    expect($message)->toBeInstanceOf(InvalidMessage::class)
        ->and($message->code)->toBe($code)
        ->and($message->id?->id)->toBe($id);
})->with([
    'not json' => ['{bad', RPCErrorCode::PARSE_ERROR, null],
    'scalar' => ['5', RPCErrorCode::INVALID_REQUEST, null],
    'empty object' => ['{}', RPCErrorCode::INVALID_REQUEST, null],
    'empty array' => ['[]', RPCErrorCode::INVALID_REQUEST, null],
    'wrong jsonrpc' => ['{"jsonrpc":"1.0","id":7,"method":"ping"}', RPCErrorCode::INVALID_REQUEST, 7],
    'missing jsonrpc' => ['{"id":7,"method":"ping"}', RPCErrorCode::INVALID_REQUEST, 7],
    'method not string' => ['{"jsonrpc":"2.0","id":7,"method":5}', RPCErrorCode::INVALID_REQUEST, 7],
    'float id' => ['{"jsonrpc":"2.0","id":1.5,"method":"ping"}', RPCErrorCode::INVALID_REQUEST, null],
    'id past int range' => ['{"jsonrpc":"2.0","id":99999999999999999999,"method":"ping"}', RPCErrorCode::INVALID_REQUEST, null],
    'null request id' => ['{"jsonrpc":"2.0","id":null,"method":"ping"}', RPCErrorCode::INVALID_REQUEST, null],
    'scalar params' => ['{"jsonrpc":"2.0","id":7,"method":"ping","params":5}', RPCErrorCode::INVALID_REQUEST, 7],
    'positional params' => ['{"jsonrpc":"2.0","id":7,"method":"ping","params":[1,2]}', RPCErrorCode::INVALID_REQUEST, 7],
    'no member' => ['{"jsonrpc":"2.0","id":7}', RPCErrorCode::INVALID_REQUEST, 7],
    'result and error' => ['{"jsonrpc":"2.0","id":7,"result":{},"error":{"code":1,"message":"x"}}', RPCErrorCode::INVALID_REQUEST, 7],
    'response without id' => ['{"jsonrpc":"2.0","result":{}}', RPCErrorCode::INVALID_REQUEST, null],
    'error without code' => ['{"jsonrpc":"2.0","id":7,"error":{"message":"x"}}', RPCErrorCode::INVALID_REQUEST, 7],
    'error not object' => ['{"jsonrpc":"2.0","id":7,"error":"x"}', RPCErrorCode::INVALID_REQUEST, 7],
    'error with float id' => ['{"jsonrpc":"2.0","id":1.5,"error":{"code":1,"message":"x"}}', RPCErrorCode::INVALID_REQUEST, null],
]);

it('accepts empty params, which decode to an empty array.', function () {
    expect(MessageDecoder::fromWire('{"jsonrpc":"2.0","id":7,"method":"ping","params":{}}')->params)->toBe([]);
});

it('decodes each batch item on its own, in wire order.', function () {
    $batch = MessageDecoder::fromWire('[{"jsonrpc":"2.0","method":"n"},1,{"jsonrpc":"2.0","id":2,"method":"ping"}]');

    expect($batch)->toBeInstanceOf(DecodedBatch::class)
        ->and($batch->messages)->toHaveCount(3)
        ->and($batch->messages[0])->toBeInstanceOf(DecodedNotification::class)
        ->and($batch->messages[1])->toBeInstanceOf(InvalidMessage::class)
        ->and($batch->messages[2])->toBeInstanceOf(DecodedRequest::class);
});

it('rejects a batch over the size limit.', function () {
    $item = '{"jsonrpc":"2.0","method":"n"}';
    $message = MessageDecoder::fromWire('['.implode(',', array_fill(0, config('mcp.max_batch_size') + 1, $item)).']');

    expect($message)->toBeInstanceOf(InvalidMessage::class)
        ->and($message->code)->toBe(RPCErrorCode::INVALID_REQUEST);
});

it('refuses a batch size that is not a positive integer.', function (mixed $size) {
    config(['mcp.max_batch_size' => $size]);

    expect(fn () => MessageDecoder::fromWire('[{"jsonrpc":"2.0","method":"n"}]'))
        ->toThrow(SuperconductorMCPException::class);
})->with([0, -1, '100', null]);

it('renders an invalid message as the JSON-RPC error reply it needs.', function (string $wire, string $reply) {
    expect(MessageDecoder::fromWire($wire)->toJson())->toBe($reply);
})->with([
    'unreadable id' => ['{bad', '{"jsonrpc":"2.0","id":null,"error":{"code":-32700,"message":"Parse error: Syntax error"}}'],
    'recovered id' => ['{"jsonrpc":"1.0","id":7,"method":"ping"}', '{"jsonrpc":"2.0","id":7,"error":{"code":-32600,"message":"The \"jsonrpc\" member must be exactly \"2.0\"."}}'],
]);

it('exposes the id of every decoded message through id().', function (string $wire, string|int|null $id) {
    expect(MessageDecoder::fromWire($wire)->id()?->id)->toBe($id);
})->with([
    'request' => ['{"jsonrpc":"2.0","id":7,"method":"ping"}', 7],
    'response' => ['{"jsonrpc":"2.0","id":"a","result":{}}', 'a'],
    'error' => ['{"jsonrpc":"2.0","id":3,"error":{"code":-32601,"message":"x"}}', 3],
    'notification' => ['{"jsonrpc":"2.0","method":"notifications/initialized"}', null],
    'invalid' => ['{"jsonrpc":"2.0","id":4}', 4],
]);
