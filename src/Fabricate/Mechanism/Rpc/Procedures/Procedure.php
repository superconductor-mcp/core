<?php

namespace Fabricate\Mechanism\Rpc\Procedures;

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\MCPResponse;

abstract class Procedure
{
    // an async procedure runs developer code that may wait on the loop; the transport runs it in a fiber
    // and keeps reading meanwhile, so pings, cancellation and progress all work while it runs
    public const bool ASYNC = false;

    public function index(MCPRequest $request, MCPServerBag $bag): MCPResponse
    {
        return $this->handle($request, $bag);
    }
}