<?php

namespace Fabricate\NutsAndBolts\Messages\Prompts;

use Fabricate\NutsAndBolts\PromptMessage;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Messages\MCPResponse;

final readonly class GetPromptResponse extends MCPResponse
{
    /**
     * @param list<PromptMessage> $messages
     */
    public function __construct(
        string|int $id,
        public array $messages = [],
        public ?string $description = null,
    ) {
        parent::__construct($id, ['messages' => $messages]);
    }

    public function toJson(): string
    {
        $result = [
            // messages is always a JSON array
            'messages' => array_map(fn (PromptMessage|array $message): array => $message instanceof PromptMessage ? $message->toArray() : $message, array_values($this->messages)),
        ];

        if (! is_null($this->description)) {
            $result = ['description' => $this->description, ...$result];
        }

        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => $result,
        ]);
    }

    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];
        $description = $result['description'] ?? null;

        if (! is_array($result['messages'] ?? null) || (! is_null($description) && ! is_string($description))) {
            throw new UnexpectedValueException('prompts/get result needs a messages array and, when present, a string description.');
        }

        return new self($response->id, array_values($result['messages']), $description);
    }
}
