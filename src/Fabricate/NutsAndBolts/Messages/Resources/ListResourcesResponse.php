<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;

final readonly class ListResourcesResponse extends MCPResponse
{
    /**
     * @param list<array> $resources each entry as its definition's toArray() renders it
     */
    public function __construct(
        string|int $id,
        public array $resources = [],
        public ?string $next_cursor = null,
    ) {
        parent::__construct(
            $id,
            is_null($next_cursor)
                ? ['resources' => $resources]
                : ['resources' => $resources, 'nextCursor' => $next_cursor],
        );
    }

    public function toJson(): string
    {
        // always a JSON array, even when nothing is registered
        $result = ['resources' => array_values($this->resources)];

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

        if (! is_array($result['resources'] ?? null) || (! is_null($cursor) && ! is_string($cursor))) {
            throw new UnexpectedValueException('resources result needs a resources array and, when present, a string nextCursor.');
        }

        return new self($response->id, array_values($result['resources']), $cursor);
    }
}
