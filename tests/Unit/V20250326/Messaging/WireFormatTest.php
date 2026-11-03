<?php

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\Common\Messaging\EmptyResult;
use Fabricate\Schema\V20250326\Messaging\Initialize\InitializedNotification;
use Fabricate\Schema\V20250326\Messaging\Ping\PingRequest;
use Fabricate\Schema\V20250326\Messaging\Progress\ProgressNotification;
use Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCBatchRequest;
use Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCBatchResponse;
use Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCError;
use Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCNotification;
use Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCResponse;

it('adds the progress message introduced in this version.', function () {
    expect((new ProgressNotification(new ProgressToken(1), 1, null, 'halfway'))->toArray()['params'])
        ->toBe(['progressToken' => 1, 'progress' => 1, 'message' => 'halfway']);
});

it('batches requests and notifications into a JSON array.', function () {
    $batch = new JSONRPCBatchRequest([
        new JSONRPCRequest('2.0', new RequestId(1), new PingRequest()),
        new JSONRPCNotification('2.0', new InitializedNotification()),
    ]);

    expect($batch->toJson())->toBe('[{"jsonrpc":"2.0","id":1,"method":"ping"},{"jsonrpc":"2.0","method":"notifications\/initialized"}]');
});

it('batches results and errors into a JSON array.', function () {
    $batch = new JSONRPCBatchResponse([
        new JSONRPCResponse('2.0', new RequestId(1), new EmptyResult()),
        new JSONRPCError('2.0', new RequestId(2), RPCErrorCode::INTERNAL_ERROR->value, 'boom'),
    ]);

    expect($batch->toJson())->toBe('[{"jsonrpc":"2.0","id":1,"result":{}},{"jsonrpc":"2.0","id":2,"error":{"code":-32603,"message":"boom"}}]');
});
