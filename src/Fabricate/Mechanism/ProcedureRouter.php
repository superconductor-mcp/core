<?php

namespace Fabricate\Mechanism;

use Fabricate\Mechanism\Rpc\Procedures\Resources\ListResourcesProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Resources\ListResourceTemplatesProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Resources\ReadResourceProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Resources\SubscribeProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Resources\UnsubscribeProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Logging\SetLevelProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Completion\CompleteProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Prompts\ListPromptsProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Prompts\GetPromptProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Tools\CallToolProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Tools\ListToolsProcedure;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\MCPResponse;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\Mechanism\Rpc\Procedures\Utilities\PingProcedure;
use Fabricate\Mechanism\Rpc\Procedures\Lifecycle\InitializeProcedure;

class ProcedureRouter
{
    protected array $routes = [
        'initialize' => InitializeProcedure::class,
        'ping' => PingProcedure::class,
        'tools/list' => ListToolsProcedure::class,
        'tools/call' => CallToolProcedure::class,
        'resources/list' => ListResourcesProcedure::class,
        'resources/read' => ReadResourceProcedure::class,
        'resources/templates/list' => ListResourceTemplatesProcedure::class,
        'resources/subscribe' => SubscribeProcedure::class,
        'resources/unsubscribe' => UnsubscribeProcedure::class,
        'logging/setLevel' => SetLevelProcedure::class,
        'completion/complete' => CompleteProcedure::class,
        'prompts/list' => ListPromptsProcedure::class,
        'prompts/get' => GetPromptProcedure::class,
    ];

    // whether the procedure routed for this request asks to run in a fiber
    public function runsAsync(MCPRequest $request): bool
    {
        $class_name = $this->routes[$request->method] ?? null;

        return ! is_null($class_name) && $class_name::ASYNC;
    }

    public function dispatch(MCPRequest $request, ?MCPServerBag $bag = null): MCPResponse
    {
        if(isset($this->routes[$request->method]))
        {
            $class_name = $this->routes[$request->method];
            $procedure = new $class_name();
            return $procedure->index($request, $bag);
        }
        else
        {
            // no procedure is routed for this method
            return new MethodNotFound($request->id, $request->method);
        }
    }
}