<?php

namespace Fabricate\NutsAndBolts\Messages\Tools;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;

final readonly class ListToolsResponse extends MCPResponse
{
    /**
     * @param list<array{name: string, description?: string, inputSchema: array{type: 'object', properties?: array<string, array>, required?: list<string>}}> $tools
     */
    public function __construct(
        string|int $id,
        public array $tools = [],
        public ?string $next_cursor = null,
    ) {
        parent::__construct(
            $id,
            is_null($next_cursor)
                ? ['tools' => $tools]
                : ['tools' => $tools, 'nextCursor' => $next_cursor],
        );
    }

    public function toJson(): string
    {
        $result = [
            // tools is always a JSON array, even when no tools are registered
            'tools' => array_map(static::encodeTool(...), array_values($this->tools)),
        ];

        // no cursor means this is the last page, so the key is left out
        if (! is_null($this->next_cursor)) {
            $result['nextCursor'] = $this->next_cursor;
        }

        $payload = [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $result,
        ];

        return json_encode($payload);
    }

    // inputSchema.properties is a JSON object; a tool with no parameters would otherwise go out as []
    protected static function encodeTool(array $tool): array
    {
        if (isset($tool['inputSchema']['properties']) && $tool['inputSchema']['properties'] === []) {
            $tool['inputSchema']['properties'] = new \stdClass();
        }

        return $tool;
    }

    // one page as the server sent it; nextCursor is only present when there is another page
    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];
        $cursor = $result['nextCursor'] ?? null;

        if (! is_array($result['tools'] ?? null) || (! is_null($cursor) && ! is_string($cursor))) {
            throw new UnexpectedValueException('tools result needs a tools array and, when present, a string nextCursor.');
        }

        return new self($response->id, array_values($result['tools']), $cursor);
    }
}
