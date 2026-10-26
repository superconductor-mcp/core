<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Resources;

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Resources\UnsubscribeRequest;
use Fabricate\NutsAndBolts\Messages\Resources\UnsubscribeResponse;

class UnsubscribeProcedure extends Procedure
{
    public function handle(UnsubscribeRequest $request, MCPServerBag $bag): UnsubscribeResponse|JsonRpcError
    {
        if (($bag->server_configuration->capabilities()['resources']['subscribe'] ?? false) !== true) {
            return new MethodNotFound($request->id, $request->method);
        }

        // unsubscribing from a uri that was never subscribed is not an error: the outcome is the same
        unset($bag->subscriptions[$request->uri]);

        return new UnsubscribeResponse($request->id);
    }
}
