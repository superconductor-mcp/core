<?php

use Fabricate\Client\ClientCapabilitiesFactory;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Core\Routing\ProcedureRouter;
use Fabricate\Core\Routing\ProcedureRouterFacade;
use Fabricate\Server\ServerCapabilitiesFactory;
use Fabricate\Server\ServerConfiguration;
use Voyager\Config\Repository;
use Voyager\Vessel\ControlPanel;

function routedRequest(string $method, string ...$markers): Request
{
    $class = match($markers) {
        [ClientRequest::class] => FromClient::class,
        [ServerRequest::class] => FromServer::class,
        default => FromEither::class,
    };

    return new $class($method);
}

readonly class FromClient implements Request, ClientRequest
{
    public function __construct(public string $method) {}
    public function toArray(): array { return ['method' => $this->method]; }
}

readonly class FromServer implements Request, ServerRequest
{
    public function __construct(public string $method) {}
    public function toArray(): array { return ['method' => $this->method]; }
}

readonly class FromEither implements Request, ClientRequest, ServerRequest
{
    public function __construct(public string $method) {}
    public function toArray(): array { return ['method' => $this->method]; }
}

function fullServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $tools = true;
        protected bool|array $prompts = true;
        protected bool|array $resources = ['subscribe' => true];
        protected bool $completions = true;
        protected bool $logging = true;
        protected bool|array $tasks = ['list' => true, 'cancel' => true];
    };
}

function fullClient(): ClientConfiguration
{
    return new class(new ClientCapabilitiesFactory()) extends ClientConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
        protected bool|array $roots = true;
        protected bool|array $sampling = true;
        protected bool|array $elicitation = true;
        protected bool|array $tasks = ['list' => true, 'cancel' => true];
    };
}

function emptyServer(): ServerConfiguration
{
    return new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
    };
}

/** A stubbed procedure answers with an error for now, which still means the gates let the call through. */
function reachesProcedure(string $version, Request $request, EntityConfiguration $configuration): bool
{
    try {
        ProcedureRouterFacade::dispatch($version, $request, $configuration);
    } catch (SuperconductorMCPException $e) {
        expect($e->getCode())->toBe(RPCErrorCode::METHOD_NOT_FOUND->value);

        return false;
    }

    return true;
}

function spyOnReports(): ArrayObject
{
    $reported = new ArrayObject();
    $handler = Mockery::mock(\Voyager\Contracts\Debug\ExceptionHandler::class);
    $handler->allows('report')->andReturnUsing(function (Throwable $e) use ($reported) { $reported->append($e); });
    app()->registerInstance(\Voyager\Contracts\Debug\ExceptionHandler::class, $handler);

    return $reported;
}

beforeEach(function () {
    ControlPanel::setInstance($panel = new ControlPanel());
    $panel->registerInstance('config', new Repository(['mcp' => require __DIR__.'/../../../config/mcp.php']));
    $panel->registerSingleton(ProcedureRouter::class);

    spyOnReports();

    require __DIR__.'/../../../routes/mcp.php';
});

afterEach(fn() => ControlPanel::setInstance(null));

it('gates every request by version and receiving entity.', function (string $method, string $sender, array $versions) {
    $marker = ['client' => ClientRequest::class, 'server' => ServerRequest::class][$sender] ?? null;
    $request = $marker ? routedRequest($method, $marker) : routedRequest($method);

    foreach(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'] as $version)
    {
        $available = in_array($version, $versions, true);

        expect(reachesProcedure($version, $request, fullServer()))->toBe($available && $sender !== 'server')
            ->and(reachesProcedure($version, $request, fullClient()))->toBe($available && $sender !== 'client');
    }
})->with([
    ['ping', 'both', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']],
    ['server/discover', 'client', ['2026-07-28']],
    ['tools/list', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['tools/call', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['prompts/list', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['prompts/get', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['resources/list', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['resources/templates/list', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['resources/read', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['resources/subscribe', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']],
    ['resources/unsubscribe', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']],
    ['subscriptions/listen', 'client', ['2026-07-28']],
    ['completion/complete', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28']],
    ['logging/setLevel', 'client', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']],
    ['tasks/get', 'both', ['2025-11-25', '2026-07-28']],
    ['tasks/result', 'both', ['2025-11-25']],
    ['tasks/list', 'both', ['2025-11-25']],
    ['tasks/cancel', 'both', ['2025-11-25', '2026-07-28']],
    ['tasks/update', 'both', ['2026-07-28']],
    ['sampling/createMessage', 'server', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']],
    ['roots/list', 'server', ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']],
    ['elicitation/create', 'server', ['2025-06-18', '2025-11-25']],
]);

it('refuses a client sending sampling to a server, using the real schema class.', function () {
    $request = new \Fabricate\Schema\V20241105\Messaging\Sampling\CreateMessageRequest([], 10);

    expect(fn() => ProcedureRouterFacade::dispatch('2024-11-05', $request, fullServer()))
        ->toThrow(SuperconductorMCPException::class, "can't receive sampling/createMessage")
        ->and(reachesProcedure('2024-11-05', $request, fullClient()))->toBeTrue();
});

it('refuses a server calling a tool on a client, using the real schema class.', function () {
    $request = new \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsRequest();

    expect(fn() => ProcedureRouterFacade::dispatch('2024-11-05', $request, fullClient()))
        ->toThrow(SuperconductorMCPException::class, "can't receive tools/list");
});

it('gets a real request past the gates and into the procedure, which lists no roots for a client naming no concrete.', function () {
    $answer = ProcedureRouterFacade::dispatch('2024-11-05', new \Fabricate\Schema\V20241105\Messaging\Roots\ListRootsRequest(), fullClient());

    expect($answer->toArray())->toBe(['roots' => []]);
});

it('has no route for initialize.', function () {
    expect(reachesProcedure('2025-06-18', routedRequest('initialize', ClientRequest::class), fullServer()))->toBeFalse();
});

it('refuses a server procedure when the server does not offer the capability.', function (string $method, string $version) {
    expect(reachesProcedure($version, routedRequest($method, ClientRequest::class), emptyServer()))->toBeFalse();
})->with([
    ['tools/list', '2026-07-28'],
    ['prompts/get', '2024-11-05'],
    ['resources/read', '2025-06-18'],
    ['resources/subscribe', '2025-06-18'],
    ['completion/complete', '2025-03-26'],
    ['logging/setLevel', '2025-11-25'],
    ['tasks/get', '2025-11-25'],
]);

it('refuses a client procedure when the client does not offer the capability.', function (string $method, string $version) {
    $client = new class(new ClientCapabilitiesFactory()) extends ClientConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28'];
    };

    expect(reachesProcedure($version, routedRequest($method, ServerRequest::class), $client))->toBeFalse();
})->with([
    ['sampling/createMessage', '2024-11-05'],
    ['roots/list', '2025-06-18'],
    ['elicitation/create', '2025-11-25'],
]);

it('checks nested capabilities, not just the top level.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2025-11-25'];
        protected bool|array $resources = true;
        protected bool|array $tasks = true;
    };
    $request = fn(string $method) => routedRequest($method, ClientRequest::class);

    expect(reachesProcedure('2025-11-25', $request('resources/list'), $server))->toBeTrue()
        ->and(reachesProcedure('2025-11-25', $request('resources/subscribe'), $server))->toBeFalse()
        ->and(reachesProcedure('2025-11-25', $request('tasks/get'), $server))->toBeTrue()
        ->and(reachesProcedure('2025-11-25', $request('tasks/list'), $server))->toBeFalse()
        ->and(reachesProcedure('2025-11-25', $request('tasks/cancel'), $server))->toBeFalse();
});

it('allows completions on 2024-11-05, which has no capability to declare it.', function () {
    expect(reachesProcedure('2024-11-05', routedRequest('completion/complete', ClientRequest::class), emptyServer()))->toBeTrue();
});

it('needs no capability for ping or discover.', function () {
    expect(reachesProcedure('2025-06-18', routedRequest('ping'), emptyServer()))->toBeTrue()
        ->and(reachesProcedure('2026-07-28', routedRequest('server/discover', ClientRequest::class), emptyServer()))->toBeTrue();
});

it('refuses a version the configuration does not support even with no capability required.', function () {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {};

    expect(fn() => ProcedureRouterFacade::dispatch('2025-06-18', routedRequest('ping'), $server))
        ->toThrow(SuperconductorMCPException::class, 'Incompatible protocol version');
});

it('refuses a request with no sender marker.', function () {
    $request = new readonly class('tools/list') implements Request {
        public function __construct(public string $method) {}
        public function toArray(): array { return []; }
    };

    expect(reachesProcedure('2025-06-18', $request, fullServer()))->toBeFalse();
});

it('passes the request, configuration and version to the procedure and returns its result.', function () {
    $server = fullServer();
    $request = routedRequest('custom/thing', ClientRequest::class);
    $result = new \Fabricate\Schema\V20250618\Messaging\Tools\ListToolsResult([]);
    $seen = [];

    ProcedureRouterFacade::group('custom', function () use ($result, &$seen) {
        ProcedureRouterFacade::procedure('thing', #[CompatibleVersions('2025-06-18')] function (Request $r, EntityConfiguration $c, string $v) use ($result, &$seen) {
            $seen = [$r, $c, $v];

            return $result;
        });
    });

    expect(ProcedureRouterFacade::dispatch('2025-06-18', $request, $server))->toBe($result)
        ->and($seen)->toBe([$request, $server, '2025-06-18'])
        ->and(fn() => ProcedureRouterFacade::dispatch('2026-07-28', $request, $server))->toThrow(SuperconductorMCPException::class);
});

it('checks the response against the version before returning it.', function (string $version, Closure $response, bool $allowed) {
    ProcedureRouterFacade::procedure('custom/thing', #[CompatibleVersions('2024-11-05', '2025-11-25', '2026-07-28')] fn() => $response());

    $reported = spyOnReports();

    $answer = ProcedureRouterFacade::dispatch($version, routedRequest('custom/thing', ClientRequest::class), fullServer());

    if($allowed) {
        expect($answer)->toBeInstanceOf(\Fabricate\Contracts\Schema\Messaging\Result::class)->and($reported)->toHaveCount(0);

        return;
    }

    $error = 'Fabricate\\Schema\\V'.str_replace('-', '', $version).'\\Messaging\\MCPError';

    expect($answer)->toBeInstanceOf($error)
        ->and($answer->toArray())->toBe(['code' => -32603, 'message' => 'Internal error'])
        ->and($reported)->toHaveCount(1)
        ->and($reported[0]->getMessage())->toContain('custom/thing returned');
})->with([
    'own version' => ['2024-11-05', fn() => new \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsResult([]), true],
    'input required on its version' => ['2026-07-28', fn() => new \Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult(), true],
    'common empty result where the version has no own' => ['2024-11-05', fn() => new \Fabricate\Schema\Common\Messaging\EmptyResult(), true],
    'own empty result' => ['2026-07-28', fn() => new \Fabricate\Schema\V20260728\Messaging\EmptyResult(), true],
    'older result on a newer version' => ['2026-07-28', fn() => new \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsResult([]), false],
    'common empty result where the version has its own' => ['2026-07-28', fn() => new \Fabricate\Schema\Common\Messaging\EmptyResult(), false],
    'input required on an older version' => ['2024-11-05', fn() => new \Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult(), false],
    'task result on a later version' => ['2026-07-28', fn() => new \Fabricate\Schema\V20251125\Messaging\Tasks\CreateTaskResult(new \Fabricate\Schema\V20251125\Task('t', \Fabricate\Schema\V20251125\Concerns\TaskStatus::WORKING, 'c', 'u', null)), false],
    'not a result' => ['2025-11-25', fn() => ['tools' => []], false],
    'nothing' => ['2025-11-25', fn() => null, false],
]);

it('gives the versions with an inline error their own error class.', function (string $error) {
    expect(new $error(-32603, 'Internal error'))->toBeInstanceOf(\Fabricate\Contracts\Schema\Messaging\MCPError::class)
        ->and(fn() => new $error(-2, 'nope'))->toThrow(SuperconductorMCPException::class, 'not a known error code');
})->with([
    \Fabricate\Schema\V20241105\Messaging\MCPError::class,
    \Fabricate\Schema\V20250326\Messaging\MCPError::class,
    \Fabricate\Schema\V20250618\Messaging\MCPError::class,
]);

it('wraps its error in the version error response.', function () {
    $error = new \Fabricate\Schema\V20251125\Messaging\MCPError(-32603, 'Internal error');

    expect(new \Fabricate\Schema\V20251125\Messaging\RPC\JSONRPCErrorResponse('2.0', new \Fabricate\Contracts\Schema\RequestId(1), $error)->toArray()['error'])
        ->toBe(['code' => -32603, 'message' => 'Internal error']);
});

it('refuses a procedure that declares no compatible versions.', function () {
    ProcedureRouterFacade::procedure('undeclared', fn() => 'ran');

    expect(reachesProcedure('2025-06-18', routedRequest('undeclared', ClientRequest::class), fullServer()))->toBeFalse();
});

it('uses one router through the facade.', function () {
    expect(ProcedureRouterFacade::procedure('a', fn() => null))->toBe(app(ProcedureRouter::class));
});

it('gives every procedure method the request, configuration and version, and the request context to those that ask.', function () {
    $routes = (fn() => $this->procedures)->call(app(ProcedureRouter::class));

    expect($routes)->toHaveCount(22);

    foreach($routes as $action)
    {
        $parameters = new ReflectionMethod(...(explode('@', $action) + [1 => 'handle']))->getParameters();

        expect(count($parameters))->toBeIn([3, 4])
            ->and($parameters[1]->getName())->toBe('entity_configuration')
            ->and($parameters[2]->getName())->toBe('protocol_version')
            ->and((string) $parameters[2]->getType())->toBe('string');

        // An async procedure's fourth parameter is the optional request context its answer is posted back with.
        if(isset($parameters[3])) {
            expect((string) $parameters[3]->getType())->toBe('?'.\Fabricate\Mechanism\RequestContext::class)
                ->and($parameters[3]->isOptional())->toBeTrue();
        }
    }
});

it('declares the results each version can return.', function (string $action, array $results) {
    $declared = array_map(
        fn(ReflectionNamedType $type) => class_basename_of($type->getName()),
        new ReflectionMethod(...explode('@', $action))->getReturnType()?->getTypes() ?? [],
    );

    sort($declared);
    sort($results);

    expect($declared)->toBe($results);
})->with([
    [\Fabricate\Core\Rpc\Procedures\Tools\ToolsProcedure::class.'@call', ['CallToolResult', 'CreateTaskResult', 'InputRequiredResult', 'Deferred']],
    [\Fabricate\Core\Rpc\Procedures\Prompts\PromptsProcedure::class.'@get', ['GetPromptResult', 'InputRequiredResult', 'Deferred']],
    [\Fabricate\Core\Rpc\Procedures\Resources\ResourcesProcedure::class.'@read', ['ReadResourceResult', 'InputRequiredResult', 'Deferred']],
    [\Fabricate\Core\Rpc\Procedures\Sampling\SamplingProcedure::class.'@handle', ['CreateMessageResult', 'CreateTaskResult', 'Deferred']],
    [\Fabricate\Core\Rpc\Procedures\Elicitation\ElicitationProcedure::class.'@handle', ['ElicitResult', 'CreateTaskResult', 'Deferred']],
    [\Fabricate\Core\Rpc\Procedures\Roots\RootsProcedure::class.'@handle', ['ListRootsResult', 'Deferred']],
]);

function class_basename_of(string $class): string
{
    return substr(strrchr($class, '\\'), 1);
}

function taskResult(): \Fabricate\Schema\V20251125\Messaging\Tasks\CreateTaskResult
{
    return new \Fabricate\Schema\V20251125\Messaging\Tasks\CreateTaskResult(
        new \Fabricate\Schema\V20251125\Task('t', \Fabricate\Schema\V20251125\Concerns\TaskStatus::WORKING, 'c', 'u', null)
    );
}

function taskServer(bool $declared): ServerConfiguration
{
    return $declared
        ? new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
            protected array $supported_protocol_versions = ['2025-11-25'];
            protected bool|array $tools = true;
            protected bool|array $tasks = ['requests' => ['tools' => ['call' => true]]];
        }
        : new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
            protected array $supported_protocol_versions = ['2025-11-25'];
            protected bool|array $tools = true;
        };
}

function callTool(bool $as_task): \Fabricate\Schema\V20251125\Messaging\Tools\CallToolRequest
{
    return new \Fabricate\Schema\V20251125\Messaging\Tools\CallToolRequest(
        new \Fabricate\Contracts\Schema\RequestId(1),
        new \Fabricate\Schema\V20251125\Messaging\Params\CallToolRequestParams('t', null, $as_task ? new \Fabricate\Schema\V20251125\TaskMetadata() : null),
    );
}

it('only allows a task result for a task request the receiver declared.', function (bool $as_task, bool $declared, bool $allowed) {
    ProcedureRouterFacade::procedure('tools/call', #[CompatibleVersions('2025-11-25')] fn() => taskResult());

    $answer = ProcedureRouterFacade::dispatch('2025-11-25', callTool($as_task), taskServer($declared));

    expect($answer instanceof \Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult)->toBe($allowed);
})->with([
    'task request, declared' => [true, true, true],
    'plain request, declared' => [false, true, false],
    'task request, not declared' => [true, false, false],
    'plain request, not declared' => [false, false, false],
]);

it('refuses another method\'s result from a replaced route.', function (string $method, Closure $response, bool $allowed) {
    ProcedureRouterFacade::procedure($method, #[CompatibleVersions('2024-11-05')] fn() => $response());

    $answer = ProcedureRouterFacade::dispatch('2024-11-05', routedRequest($method, ClientRequest::class), fullServer());

    expect($answer instanceof \Fabricate\Contracts\Schema\Messaging\Result)->toBe($allowed);
})->with([
    'right result' => ['tools/list', fn() => new \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsResult([]), true],
    'wrong result' => ['tools/list', fn() => new \Fabricate\Schema\V20241105\Messaging\Tools\CallToolResult([]), false],
    'empty result for ping' => ['ping', fn() => new \Fabricate\Schema\Common\Messaging\EmptyResult(), true],
    'list result for ping' => ['ping', fn() => new \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsResult([]), false],
]);

it('turns a fault in a procedure into an internal error and reports it.', function () {
    $reported = spyOnReports();

    ProcedureRouterFacade::procedure('custom/fault', #[CompatibleVersions('2024-11-05')] fn() => throw new RuntimeException('secret detail'));

    $answer = ProcedureRouterFacade::dispatch('2024-11-05', routedRequest('custom/fault', ClientRequest::class), fullServer());

    expect($answer->toArray())->toBe(['code' => -32603, 'message' => 'Internal error'])
        ->and($reported)->toHaveCount(1)
        ->and($reported[0]->getMessage())->toBe('secret detail');
});

it('answers with a procedure\'s deliberate error code and message.', function () {
    $reported = spyOnReports();

    ProcedureRouterFacade::procedure('custom/bad', #[CompatibleVersions('2024-11-05')] fn() => throw new SuperconductorMCPException('No such tool', RPCErrorCode::INVALID_PARAMS->value));

    $answer = ProcedureRouterFacade::dispatch('2024-11-05', routedRequest('custom/bad', ClientRequest::class), fullServer());

    expect($answer->toArray())->toBe(['code' => -32602, 'message' => 'No such tool'])
        ->and($reported)->toHaveCount(0);
});

it('treats an error code the version lacks as a fault.', function () {
    $reported = spyOnReports();

    ProcedureRouterFacade::procedure('custom/odd', #[CompatibleVersions('2024-11-05')] fn() => throw new SuperconductorMCPException('Needs a URL', -32042));

    $answer = ProcedureRouterFacade::dispatch('2024-11-05', routedRequest('custom/odd', ClientRequest::class), fullServer());

    expect($answer->toArray())->toBe(['code' => -32603, 'message' => 'Internal error'])
        ->and($reported)->toHaveCount(1);
});

it('lets the event loop cancel a procedure.', function () {
    ProcedureRouterFacade::procedure('custom/cancel', #[CompatibleVersions('2024-11-05')] fn() => throw new \Voyager\Contracts\IOPools\CancelledException('stopped'));

    ProcedureRouterFacade::dispatch('2024-11-05', routedRequest('custom/cancel', ClientRequest::class), fullServer());
})->throws(\Voyager\Contracts\IOPools\CancelledException::class);

it('reports a procedure class it cannot build.', function () {
    $reported = spyOnReports();

    ProcedureRouterFacade::procedure('custom/unbuildable', UnbuildableProcedure::class);

    $answer = ProcedureRouterFacade::dispatch('2024-11-05', routedRequest('custom/unbuildable', ClientRequest::class), fullServer());

    expect($answer->toArray()['code'])->toBe(-32603)->and($reported)->toHaveCount(1);
});

#[CompatibleVersions('2024-11-05')]
class UnbuildableProcedure
{
    public function __construct(NoSuchDependency $missing) {}
    public function handle() {}
}

it('names a broken route rather than leaking a reflection error.', function (string $action) {
    ProcedureRouterFacade::procedure('custom/broken', $action);

    ProcedureRouterFacade::dispatch('2024-11-05', routedRequest('custom/broken', ClientRequest::class), fullServer());
})->with(['NoSuchProcedure', 'Fabricate\\Core\\Rpc\\Procedures\\Tools\\ToolsProcedure@nope'])
    ->throws(SuperconductorMCPException::class, 'The route for custom/broken is broken');

it('refuses a request object from another version.', function () {
    $request = new \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsRequest();

    try {
        ProcedureRouterFacade::dispatch('2025-06-18', $request, fullServer());
        $this->fail('Expected a refusal.');
    } catch(SuperconductorMCPException $e) {
        expect($e->getCode())->toBe(RPCErrorCode::INVALID_REQUEST->value)->and($e->getMessage())->toContain('is not a 2025-06-18 request');
    }
});

it('takes the flat message out of the envelope on versions that wrap it.', function (string $version, string $envelope, string $flat) {
    $seen = null;

    ProcedureRouterFacade::procedure('tools/list', #[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18')] function (Request $request) use (&$seen) {
        $seen = $request;
    });

    $message = new $flat();
    ProcedureRouterFacade::dispatch($version, new $envelope('2.0', new \Fabricate\Contracts\Schema\RequestId(1), $message), fullServer());

    expect($seen)->toBe($message);
})->with([
    ['2024-11-05', \Fabricate\Schema\V20241105\Messaging\RPC\JSONRPCRequest::class, \Fabricate\Schema\V20241105\Messaging\Tools\ListToolsRequest::class],
    ['2025-03-26', \Fabricate\Schema\V20250326\Messaging\RPC\JSONRPCRequest::class, \Fabricate\Schema\V20250326\Messaging\Tools\ListToolsRequest::class],
    ['2025-06-18', \Fabricate\Schema\V20250618\Messaging\RPC\JSONRPCRequest::class, \Fabricate\Schema\V20250618\Messaging\Tools\ListToolsRequest::class],
]);

it('gives an unsupported version its own code, ahead of any method check.', function (string $version, int $code) {
    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2025-06-18'];
    };

    try {
        ProcedureRouterFacade::dispatch($version, routedRequest('no/such/method', ClientRequest::class), $server);
        $this->fail('Expected a refusal.');
    } catch(SuperconductorMCPException $e) {
        expect($e->getCode())->toBe($code)->and($e->getMessage())->toContain('Incompatible protocol version');
    }
})->with([
    'handshake version' => ['2025-03-26', -32602],
    'stateless version' => ['2026-07-28', -32022],
    'unknown version' => ['1999-01-01', -32022],
]);

it('lists the versions supported by both the configuration and the application.', function () {
    config()->set('mcp.supported_protocol_versions', ['2025-06-18', '2026-07-28']);

    $server = new class(new ServerCapabilitiesFactory()) extends ServerConfiguration {
        protected array $supported_protocol_versions = ['2024-11-05', '2026-07-28', '2025-06-18'];
    };

    expect($server->supportedProtocolVersions())->toBe(['2026-07-28', '2025-06-18']);
});

it('answers a ping with an empty result in every version that has ping.', function (string $version) {
    $ping = \Fabricate\Mechanism\MessageHydrator::hydrateAs(\Fabricate\Mechanism\MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"ping"}'), $version);

    $answer = ProcedureRouterFacade::dispatch($version, $ping, fullServer());

    expect($answer)->toBeInstanceOf(\Fabricate\Contracts\Schema\Messaging\EmptyResult::class)
        ->and($answer->toArray())->toBe([]);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('stores the requested log level on the server configuration it was dispatched with.', function (string $version) {
    $server = fullServer();
    $request = \Fabricate\Mechanism\MessageHydrator::hydrateAs(\Fabricate\Mechanism\MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"logging/setLevel","params":{"level":"warning"}}'), $version);

    expect($request->level())->toBe(\Fabricate\Contracts\Schema\LoggingLevel::WARNING)
        ->and($server->logLevel())->toBeNull();

    $answer = ProcedureRouterFacade::dispatch($version, $request, $server);

    expect($answer)->toBeInstanceOf(\Fabricate\Contracts\Schema\Messaging\EmptyResult::class)
        ->and($server->logLevel())->toBe(\Fabricate\Contracts\Schema\LoggingLevel::WARNING);
})->with(['2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25']);

it('refuses logging/setLevel from a server without the logging capability.', function () {
    $request = \Fabricate\Mechanism\MessageHydrator::hydrateAs(\Fabricate\Mechanism\MessageDecoder::fromWire('{"jsonrpc":"2.0","id":1,"method":"logging/setLevel","params":{"level":"info"}}'), '2025-06-18');

    expect(fn() => ProcedureRouterFacade::dispatch('2025-06-18', $request, emptyServer()))
        ->toThrow(SuperconductorMCPException::class);
});
