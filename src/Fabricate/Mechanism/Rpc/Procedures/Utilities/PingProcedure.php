<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Utilities;

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Ping\PingRequest;
use Fabricate\NutsAndBolts\Messages\Ping\PingResponse;


class PingProcedure extends Procedure
{
    public function handle(PingRequest $request, MCPServerBag $bag): PingResponse
    {
        // a ping only proves the server is alive: answer at once, echoing the request's id
        return new PingResponse($request->id);
    }
}