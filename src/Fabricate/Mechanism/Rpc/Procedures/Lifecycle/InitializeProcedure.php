<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Lifecycle;

use ReflectionException;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeRequest;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;

class InitializeProcedure extends Procedure
{
    /**
     * @throws ReflectionException
     */
    public function handle(InitializeRequest $request, MCPServerBag $bag): InitializeResponse
    {
        // Put Client info in the bag
        $bag->put('client_info', $request->client_info);
        $bag->put('client_version', $request->protocol_version);
        $bag->put('client_capabilities', $request->capabilities);

        $server = $bag->server_configuration;

        // echo the client's version when we speak it; otherwise offer our latest and let the client decide
        $supported = config('mcp.servers.supported_mcp_versions', []);
        $protocol_version = in_array($request->protocol_version, $supported, true)
            ? $request->protocol_version
            : max($supported); // versions are ISO dates, so the greatest string is the latest

        $bag->protocol_version = $protocol_version;
        $capabilities = $server->capabilities();

        return new InitializeResponse(
            $request->id,
            $protocol_version,
            $capabilities,
            $server->serverInfo()
        );
    }
}