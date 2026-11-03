<?php

use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Capabilities\Elicitation\StringSchema;
use Fabricate\Schema\V20250618\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20250618\Concerns\ResourceLink;
use Fabricate\Schema\V20250618\Implementation;
use Fabricate\Schema\V20250618\Messaging\Elicitation\ElicitRequest;
use Fabricate\Schema\V20250618\Messaging\Elicitation\ElicitResult;
use Fabricate\Schema\V20250618\Messaging\Ping\PingRequest;

it('merges caller _meta with the progressToken key.', function () {
    expect((new PingRequest(['a' => 1], new ProgressToken('p')))->toArray()['params'])
        ->toBe(['_meta' => ['a' => 1, 'progressToken' => 'p']]);
});

it('constructs an Implementation with a title.', function () {
    expect((new Implementation('x', '1', 'X'))->toArray())->toBe(['name' => 'x', 'title' => 'X', 'version' => '1']);
});

it('writes a resource link with its type.', function () {
    expect((new ResourceLink('file:///a', 'a'))->toArray())->toBe(['name' => 'a', 'uri' => 'file:///a', 'type' => 'resource_link']);
});

it('only writes an output schema when the tool declares one.', function () {
    expect((new ToolDefinition('t'))->toArray())->not->toHaveKey('outputSchema')
        ->and((new ToolDefinition('t', has_output_schema: true))->toArray()['outputSchema'])->toBe(['type' => 'object']);
});

it('builds an elicitation request and its result.', function () {
    $request = new ElicitRequest('Name?', ['name' => new StringSchema()], ['name']);

    expect($request->toArray()['params']['requestedSchema'])->toBe([
        'type' => 'object',
        'properties' => ['name' => ['type' => 'string']],
        'required' => ['name'],
    ])->and((new ElicitResult(ElicitAction::DECLINE))->toArray())->toBe(['action' => 'decline']);
});
