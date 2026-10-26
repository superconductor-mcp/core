<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Resources;

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\ResourceDefinition;
use Fabricate\NutsAndBolts\ResourceTemplateDefinition;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Errors\ResourceNotFound;
use Fabricate\NutsAndBolts\Messages\Resources\SubscribeRequest;
use Fabricate\NutsAndBolts\Messages\Resources\SubscribeResponse;

class SubscribeProcedure extends Procedure
{
    public function handle(SubscribeRequest $request, MCPServerBag $bag): SubscribeResponse|JsonRpcError
    {
        // subscriptions exist only on a server that advertised resources.subscribe
        if (($bag->server_configuration->capabilities()['resources']['subscribe'] ?? false) !== true) {
            return new MethodNotFound($request->id, $request->method);
        }

        // only a uri the server can read can be watched
        if (! $this->offered($request->uri, $bag)) {
            return new ResourceNotFound($request->id, $request->uri);
        }

        $bag->subscriptions[$request->uri] = true;

        return new SubscribeResponse($request->id);
    }

    protected function offered(string $uri, MCPServerBag $bag): bool
    {
        foreach ($bag->server_configuration->resources() as $resource) {
            if ($resource instanceof ResourceDefinition && $resource->uri === $uri) {
                return true;
            }
        }

        foreach ($bag->server_configuration->resourceTemplates() as $template) {
            if ($template instanceof ResourceTemplateDefinition && ! is_null($template->match($uri))) {
                return true;
            }
        }

        return false;
    }
}
