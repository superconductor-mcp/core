<?php

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\EmptyResult;
use Fabricate\Schema\V20241105\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20241105\Implementation;
use Fabricate\Schema\V20241105\Messaging\Initialize\InitializeRequest;
use Fabricate\Schema\V20241105\Messaging\Ping\PingRequest;
use Fabricate\Schema\V20241105\Messaging\Progress\ProgressNotification;
use Fabricate\Schema\V20241105\Messaging\Resources\ListResourcesRequest;
use Fabricate\Schema\V20241105\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20241105\Messaging\RPC\JSONRPCResponse;

it('unwraps value objects to their scalar wire values.', function () {
    expect((new ProgressNotification(new ProgressToken('t'), 1.5, 10))->toArray())->toBe([
        'method' => 'notifications/progress',
        'params' => ['progressToken' => 't', 'progress' => 1.5, 'total' => 10],
    ])->and((new ListResourcesRequest(new Cursor('c')))->toArray()['params'])->toBe(['cursor' => 'c']);
});

it('encodes empty capabilities and empty capability payloads as objects.', function () {
    $request = new InitializeRequest('2024-11-05', new ClientCapabilities(), new Implementation('x', '1'));
    expect(json_encode($request->toArray()))->toContain('"capabilities":{}');

    $request = new InitializeRequest('2024-11-05', new ClientCapabilities(sampling: true), new Implementation('x', '1'));
    expect(json_encode($request->toArray()))->toContain('"capabilities":{"sampling":{}}');
});

it('wraps a message in the JSON-RPC envelope.', function () {
    $request = new JSONRPCRequest('2.0', new RequestId(1), new PingRequest());

    expect($request->toJson())->toBe('{"jsonrpc":"2.0","id":1,"method":"ping"}');
});

it('encodes an empty result as an object.', function () {
    expect((new JSONRPCResponse('2.0', new RequestId('a'), new EmptyResult()))->toJson())
        ->toBe('{"jsonrpc":"2.0","id":"a","result":{}}');
});
