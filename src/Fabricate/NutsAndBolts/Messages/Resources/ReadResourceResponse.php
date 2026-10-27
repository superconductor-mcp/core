<?php

namespace Fabricate\NutsAndBolts\Messages\Resources;

use Fabricate\NutsAndBolts\Messages\MCPResponse;
use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use InvalidArgumentException;
use Fabricate\NutsAndBolts\Schema\Content;
use Fabricate\NutsAndBolts\Schema\ResourceContents;

final readonly class ReadResourceResponse extends MCPResponse
{
    /**
     * @param list<ResourceContents> $contents one entry per resource the read returned (a uri can expand to several)
     */
    public function __construct(
        string|int $id,
        public array $contents = [],
    ) {
        parent::__construct($id, ['contents' => $contents]);
    }

    public function toJson(): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $this->id,
            'result' => [
                'contents' => array_map(
                    fn (ResourceContents $contents): array => $contents->toArray(),
                    array_values($this->contents),
                ),
            ],
        ]);
    }

    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];

        if (! is_array($result['contents'] ?? null)) {
            throw new UnexpectedValueException('resources/read result needs a contents array.');
        }

        try {
            return new self($response->id, array_map(Content::resource(...), array_values($result['contents'])));
        } catch (InvalidArgumentException $e) {
            throw new UnexpectedValueException("resources/read contents: {$e->getMessage()}");
        }
    }
}
