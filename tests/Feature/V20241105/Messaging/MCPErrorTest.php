<?php

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\V20241105\Messaging\RPC\JSONRPCError;

it('sets $id to null when the logic is unable to parse the id out of an offending message.', function () {
    $error = new JSONRPCError('2.0', null, RPCErrorCode::PARSE_ERROR->value, 'Parse error');

    expect($error->id)->toBeNull()
        ->and($error->toArray())->toHaveKey('id', null)
        ->and($error->toJson())->toBe('{"jsonrpc":"2.0","id":null,"error":{"code":-32700,"message":"Parse error"}}');
});

it('rejects any arbitrary $error->code value that is not an integer from an RPCErrorCode or MCPErrorCode.', function () {
    new JSONRPCError('2.0', null, -2, 'Nope');
})->throws(SuperconductorMCPException::class, 'not a known error code');
