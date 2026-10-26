<?php

namespace Fabricate\NutsAndBolts\Messages\Prompts;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;

final readonly class ListPromptsResponse extends MCPResponse
{
    /**
     * @param list<array> $prompts each entry as PromptDefinition::toArray() renders it
     */
    public function __construct(
        string|int $id,
        public array $prompts = [],
        public ?string $next_cursor = null,
    ) {
        parent::__construct(
            $id,
            is_null($next_cursor) ? ['prompts' => $prompts] : ['prompts' => $prompts, 'nextCursor' => $next_cursor],
        );
    }

    public function toJson(): string
    {
        // always a JSON array, even when no prompts are offered
        $result = ['prompts' => array_values($this->prompts)];

        // no cursor means this is the last page, so the key is left out
        if (! is_null($this->next_cursor)) {
            $result['nextCursor'] = $this->next_cursor;
        }

        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $result,
        ]);
    }

    // one page as the server sent it; nextCursor is only present when there is another page
    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];
        $cursor = $result['nextCursor'] ?? null;

        if (! is_array($result['prompts'] ?? null) || (! is_null($cursor) && ! is_string($cursor))) {
            throw new UnexpectedValueException('prompts result needs a prompts array and, when present, a string nextCursor.');
        }

        return new self($response->id, array_values($result['prompts']), $cursor);
    }
}
