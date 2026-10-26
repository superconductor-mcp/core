<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Tools;

use Fabricate\Contracts\NutsAndBolts\ToolDefinition;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Paginator;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsRequest;
use Fabricate\NutsAndBolts\Messages\Tools\ListToolsResponse;

class ListToolsProcedure extends Procedure
{
    public function handle(ListToolsRequest $request, MCPServerBag $bag): ListToolsResponse|JsonRpcError
    {
        try {
            [$page, $next_cursor] = Paginator::page($bag->server_configuration->tools(), $request->cursor, (int) config('mcp.pagination.page_size', 50));
        } catch (InvalidArgumentException) {
            return new InvalidParams($request->id, 'Invalid cursor.');
        }

        return new ListToolsResponse($request->id, array_map(fn (ToolDefinition $tool): array => $this->describe($tool), $page), $next_cursor);
    }

    /**
     * Turns one entry of MCPServer::tools() into the wire shape tools/list sends:
     * name, optional description, and an inputSchema of type "object".
     * Each definition renders itself; any parsing or merging across definitions lands here.
     */
    protected function describe(ToolDefinition $tool): array
    {
        return $tool->toArray();
    }
}