<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Logging;

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\MethodNotFound;
use Fabricate\NutsAndBolts\Messages\Logging\SetLevelRequest;
use Fabricate\NutsAndBolts\Messages\Logging\SetLevelResponse;

class SetLevelProcedure extends Procedure
{
    public function handle(SetLevelRequest $request, MCPServerBag $bag): SetLevelResponse|JsonRpcError
    {
        // only a server that advertised logging takes a level
        if (! array_key_exists('logging', $bag->server_configuration->capabilities())) {
            return new MethodNotFound($request->id, $request->method);
        }

        // from now on this session only receives messages at this level or above
        $bag->log_level = $request->level;

        return new SetLevelResponse($request->id);
    }
}
