<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Resources;

use InvalidArgumentException;
use Fabricate\NutsAndBolts\Paginator;

use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\ResourceDefinition;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourcesRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourcesResponse;

class ListResourcesProcedure extends Procedure
{
    public function handle(ListResourcesRequest $request, MCPServerBag $bag): ListResourcesResponse|JsonRpcError
    {
        try {
            [$page, $next_cursor] = Paginator::page($bag->server_configuration->resources(), $request->cursor, (int) config('mcp.pagination.page_size', 50));
        } catch (InvalidArgumentException) {
            return new InvalidParams($request->id, 'Invalid cursor.');
        }

        return new ListResourcesResponse($request->id, array_map(fn (ResourceDefinition $resource): array => $resource->toArray(), $page), $next_cursor);
    }
}
