<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Resources;

use InvalidArgumentException;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Paginator;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\ResourceTemplateDefinition;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourceTemplatesRequest;
use Fabricate\NutsAndBolts\Messages\Resources\ListResourceTemplatesResponse;

class ListResourceTemplatesProcedure extends Procedure
{
    public function handle(ListResourceTemplatesRequest $request, MCPServerBag $bag): ListResourceTemplatesResponse|JsonRpcError
    {
        try {
            [$page, $next_cursor] = Paginator::page($bag->server_configuration->resourceTemplates(), $request->cursor, (int) config('mcp.pagination.page_size', 50));
        } catch (InvalidArgumentException) {
            return new InvalidParams($request->id, 'Invalid cursor.');
        }

        return new ListResourceTemplatesResponse($request->id, array_map(fn (ResourceTemplateDefinition $template): array => $template->toArray(), $page), $next_cursor);
    }
}
