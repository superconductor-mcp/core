<?php

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20260728\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20260728\Capabilities\ServerCapabilities;
use Fabricate\Schema\V20260728\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20260728\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20260728\Concerns\CacheScope;
use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Fabricate\Schema\V20260728\Concerns\TextContent;
use Fabricate\Schema\V20260728\JSONArray;
use Fabricate\Schema\V20260728\JSONObject;
use Fabricate\Schema\V20260728\JSONValue;
use Fabricate\Schema\V20260728\Messaging\Discover\DiscoverRequest;
use Fabricate\Schema\V20260728\Messaging\Discover\DiscoverResult;
use Fabricate\Schema\V20260728\Messaging\Discover\DiscoverResultResponse;
use Fabricate\Schema\V20260728\Messaging\Errors\UnsupportedProtocolVersionError;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;
use Fabricate\Schema\V20260728\Messaging\Params\NotificationParams;
use Fabricate\Schema\V20260728\Messaging\Params\RequestParams;
use Fabricate\Schema\V20260728\Messaging\Progress\ProgressNotification;
use Fabricate\Schema\V20260728\Messaging\Params\ProgressNotificationParams;
use Fabricate\Schema\V20260728\Messaging\Roots\ListRootsResult;
use Fabricate\Schema\V20260728\Messaging\Sampling\CreateMessageResult;
use Fabricate\Contracts\Schema\Messaging\ClientNotification;
use Fabricate\Contracts\Schema\Messaging\ServerNotification;
use Fabricate\Contracts\Schema\Messaging\Result;

it('requires protocol metadata on every request.', function () {
    $request = new DiscoverRequest(new RequestId(1), new RequestParams(new RequestMetaObject('2026-07-28', new ClientCapabilities())));

    expect($request->toJson())->toBe('{"jsonrpc":"2.0","id":1,"method":"server\/discover","params":{"_meta":{"io.modelcontextprotocol\/protocolVersion":"2026-07-28","io.modelcontextprotocol\/clientCapabilities":{}}}}');
});

it('writes resultType and the cache hints on a cacheable result.', function () {
    $response = new DiscoverResultResponse(new RequestId(1), new DiscoverResult(['2026-07-28'], new ServerCapabilities(), 1000, CacheScope::PRIVATE));
    $result = $response->toArray()['result'];

    expect($result['resultType'])->toBe('complete')
        ->and($result['ttlMs'])->toBe(1000)
        ->and($result['cacheScope'])->toBe('private');
});

it('defaults an input required result to its result type.', function () {
    expect((new InputRequiredResult())->toArray())->toBe(['resultType' => 'input_required']);
});

it('treats sampling and roots results as input responses rather than protocol results.', function () {
    $sampling = new CreateMessageResult('m', Role::ASSISTANT, new TextContent('hi'));
    $roots = new ListRootsResult([]);

    expect($sampling)->toBeInstanceOf(InputResponse::class)->toBeInstanceOf(SamplingMessage::class)
        ->and($sampling->toArray())->not->toHaveKey('resultType')
        ->and($roots)->toBeInstanceOf(InputResponse::class)
        ->and($roots->toArray())->toBe(['roots' => []]);
});

it('marks progress notifications as server only in this version.', function () {
    $progress = new ProgressNotification(new ProgressNotificationParams(new ProgressToken(1), 1));

    expect($progress)->toBeInstanceOf(ServerNotification::class)->not->toBeInstanceOf(ClientNotification::class);
});

it('writes the subscription id into notification meta.', function () {
    expect((new NotificationParams(new NotificationMetaObject(new RequestId('s'))))->toArray())
        ->toBe(['_meta' => ['io.modelcontextprotocol/subscriptionId' => 's']]);
});

it('resolves JSON values including null, floats and nested empties.', function () {
    $object = new JSONObject([
        'a' => new JSONValue(null),
        'b' => new JSONValue(1.5),
        'c' => new JSONValue(new JSONObject()),
        'd' => new JSONValue(new JSONArray([new JSONValue('x')])),
    ]);

    expect(json_encode($object->toArray()))->toBe('{"a":null,"b":1.5,"c":{},"d":["x"]}');
});

it('forces the tool input schema to an object and keeps open keywords.', function () {
    $tool = new ToolDefinition('t', null, ['properties' => ['a' => []], 'additionalProperties' => false], []);

    expect(json_encode($tool->toArray()))->toBe('{"name":"t","inputSchema":{"properties":{"a":{}},"additionalProperties":false,"type":"object"},"outputSchema":{}}');
});

it('carries both version lists on the unsupported version error.', function () {
    $error = new UnsupportedProtocolVersionError(new RequestId(1), 'no', ['2026-07-28'], '1999-01-01')->toArray()['error'];

    expect($error['code'])->toBe(-32022)
        ->and($error['data'])->toBe(['supported' => ['2026-07-28'], 'requested' => '1999-01-01']);
});
