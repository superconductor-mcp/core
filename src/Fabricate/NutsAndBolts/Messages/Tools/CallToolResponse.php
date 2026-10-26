<?php

namespace Fabricate\NutsAndBolts\Messages\Tools;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Voyager\Contracts\NutsAndBolts\Arrayable;

final readonly class CallToolResponse extends MCPResponse
{
    /**
     * @param list<Arrayable|array> $content text, image or embedded-resource items, as content DTOs or ready-built arrays
     * @param bool $is_error true when the tool ran but failed; the failure is reported in the content, not as a JSON-RPC error
     */
    public function __construct(
        string|int $id,
        public array $content = [],
        public bool $is_error = false,
    ) {
        parent::__construct(
            $id,
            [
                'content' => $content,
                'isError' => $is_error,
            ],
        );
    }

    public function toJson(): string
    {
        $result = [
            // content is always a JSON array, even when the tool returned nothing
            'content' => array_map(
                fn (Arrayable|array $item): array => $item instanceof Arrayable ? $item->toArray() : $item,
                array_values($this->content),
            ),
        ];

        // isError is optional and defaults to false, so it only goes out when the tool failed
        if ($this->is_error) {
            $result['isError'] = true;
        }

        $payload = [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $result,
        ];

        return json_encode($payload);
    }

    // the tool's result as the server sent it; content items stay as the arrays they arrived as
    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];

        if (! is_array($result['content'] ?? null) || ! is_bool($result['isError'] ?? false)) {
            throw new UnexpectedValueException('tools/call result needs a content array and, when present, a boolean isError.');
        }

        return new self($response->id, array_values($result['content']), $result['isError'] ?? false);
    }
}
