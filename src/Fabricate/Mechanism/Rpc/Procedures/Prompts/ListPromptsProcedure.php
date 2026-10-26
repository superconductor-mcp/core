<?php

namespace Fabricate\Mechanism\Rpc\Procedures\Prompts;

use InvalidArgumentException;
use Fabricate\NutsAndBolts\MCPServerBag;
use Fabricate\NutsAndBolts\Paginator;
use Fabricate\NutsAndBolts\PromptDefinition;
use Fabricate\Mechanism\Rpc\Procedures\Procedure;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Prompts\ListPromptsRequest;
use Fabricate\NutsAndBolts\Messages\Prompts\ListPromptsResponse;

class ListPromptsProcedure extends Procedure
{
    public function handle(ListPromptsRequest $request, MCPServerBag $bag): ListPromptsResponse|JsonRpcError
    {
        try {
            [$page, $next_cursor] = Paginator::page($bag->server_configuration->prompts(), $request->cursor, (int) config('mcp.pagination.page_size', 50));
        } catch (InvalidArgumentException) {
            return new InvalidParams($request->id, 'Invalid cursor.');
        }

        return new ListPromptsResponse($request->id, array_map(fn (PromptDefinition $prompt): array => $prompt->toArray(), $page), $next_cursor);
    }
}
