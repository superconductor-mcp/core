<?php

namespace Fabricate\NutsAndBolts\Messages\Tools;

use Fabricate\NutsAndBolts\Meta;
use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Schema\Content;
use Voyager\Contracts\NutsAndBolts\Arrayable;
use Fabricate\Contracts\Mechanism\SchemaPrimitive;

final readonly class CallToolResponse extends MCPResponse
{
    /**
     * @param list<SchemaPrimitive&Arrayable> $content text, image, audio, embedded-resource and resource-link items
     * @param bool $is_error true when the tool ran but failed; the failure is reported in the content, not as a JSON-RPC error
     */
    public function __construct(
        string|int $id,
        public array $content = [],
        public bool $is_error = false,
        // 2025-06-18: the tool's result as a JSON object, beside the content
        public ?array $structured = null,
        public array $meta = [],
    ) {
        Meta::check($meta);

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
                fn (SchemaPrimitive&Arrayable $item): array => $item->toArray(),
                array_values($this->content),
            ),
        ];

        if (! is_null($this->structured)) {
            $result['structuredContent'] = (object) $this->structured;
        }

        // isError is optional and defaults to false, so it only goes out when the tool failed
        if ($this->is_error) {
            $result['isError'] = true;
        }

        $result = Meta::attach($result, $this->meta);

        $payload = [
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $result,
        ];

        return json_encode($payload);
    }

    // the tool's result as the server sent it, its content read into the same primitives a tool returns
    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];

        if (! is_array($result['content'] ?? null) || ! is_bool($result['isError'] ?? false)) {
            throw new UnexpectedValueException('tools/call result needs a content array and, when present, a boolean isError.');
        }

        $structured = $result['structuredContent'] ?? null;

        if (! is_null($structured) && (! is_array($structured) || ($structured !== [] && array_is_list($structured)))) {
            throw new UnexpectedValueException('tools/call structuredContent must be a JSON object.');
        }

        try {
            $content = array_map(Content::item(...), array_values($result['content']));
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedValueException("tools/call content: {$e->getMessage()}");
        }

        return new self($response->id, $content, $result['isError'] ?? false, $structured, is_array($result['_meta'] ?? null) ? $result['_meta'] : []);
    }
}
