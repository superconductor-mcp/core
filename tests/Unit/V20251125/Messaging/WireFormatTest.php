<?php

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\ElicitationErrorCode;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20251125\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20251125\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20251125\Concerns\TaskStatus;
use Fabricate\Schema\V20251125\Concerns\ToolUseContent;
use Fabricate\Schema\V20251125\Implementation;
use Fabricate\Schema\V20251125\Messaging\Errors\URLElicitationRequiredError;
use Fabricate\Schema\V20251125\Messaging\Initialize\InitializeRequest;
use Fabricate\Schema\V20251125\Messaging\MCPError;
use Fabricate\Schema\V20251125\Messaging\Params\ElicitRequestURLParams;
use Fabricate\Schema\V20251125\Messaging\Params\InitializeRequestParams;
use Fabricate\Schema\V20251125\Messaging\Params\RequestParams;
use Fabricate\Schema\V20251125\Messaging\Params\TaskStatusNotificationParams;
use Fabricate\Schema\V20251125\Messaging\Ping\PingRequest;
use Fabricate\Schema\V20251125\Messaging\RPC\JSONRPCErrorResponse;
use Fabricate\Schema\V20251125\Messaging\Tasks\TaskStatusNotification;
use Fabricate\Schema\V20251125\Task;
use Fabricate\Contracts\Core\SuperconductorMCPException;

it('writes the envelope and camelCase params on requests.', function () {
    $request = new InitializeRequest(new RequestId(1), new InitializeRequestParams('2025-11-25', new ClientCapabilities(), new Implementation('x', '1')));

    expect($request->toJson())->toBe('{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"x","version":"1"}}}');
});

it('encodes nested task capability payloads as objects.', function () {
    $capabilities = new ClientCapabilities(tasks: true, support_tasks_requests: true, support_tasks_sampling_requests: true, support_tasks_sampling_create_message_requests: true);

    expect(json_encode($capabilities->toArray()))->toBe('{"tasks":{"requests":{"sampling":{"createMessage":{}}}}}');
});

it('writes icons as an array of icon objects.', function () {
    expect((new Implementation('x', '1', [new Icon('https://x/y.png')]))->toArray()['icons'])->toBe([['src' => 'https://x/y.png']]);
});

it('writes empty params as an object.', function () {
    expect((new PingRequest(new RequestId(1), new RequestParams()))->toJson())->toBe('{"jsonrpc":"2.0","id":1,"method":"ping","params":{}}');
});

it('accepts a single sampling block or a list of them.', function () {
    $use = new ToolUseContent('u1', 't', []);

    expect(json_encode((new SamplingMessage(Role::ASSISTANT, $use))->toArray()))->toContain('"content":{"type":"tool_use"')
        ->and(json_encode((new SamplingMessage(Role::ASSISTANT, [$use]))->toArray()))->toContain('"content":[{"type":"tool_use"')
        ->and(json_encode($use->toArray()))->toContain('"input":{}');
});

it('flattens the task into its notification params and always writes ttl.', function () {
    $task = new Task('t1', TaskStatus::WORKING, 'c', 'u', null);
    $params = (new TaskStatusNotification(new TaskStatusNotificationParams($task)))->toArray()['params'];

    expect($params)->toHaveKey('taskId', 't1')->toHaveKey('ttl', null)->not->toHaveKey('task');
});

it('omits a missing id on error responses and rejects unknown codes.', function () {
    $error = new JSONRPCErrorResponse('2.0', null, new MCPError(RPCErrorCode::PARSE_ERROR->value, 'bad'));

    expect($error->toArray())->not->toHaveKey('id')
        ->and(fn() => new MCPError(-2, 'nope'))->toThrow(SuperconductorMCPException::class);
});

it('carries elicitations on the URL elicitation error.', function () {
    $error = new URLElicitationRequiredError(new RequestId(1), 'need', [new ElicitRequestURLParams('m', 'e1', 'https://x')])->toArray()['error'];

    expect($error['code'])->toBe(ElicitationErrorCode::URL_ELICITATION_REQUIRED->value)
        ->and($error['data']['elicitations'][0])->toBe(['mode' => 'url', 'message' => 'm', 'elicitationId' => 'e1', 'url' => 'https://x']);
});
