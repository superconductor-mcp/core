<?php

namespace Fabricate\Schema\V20251125\Concerns;

readonly class ToolResultContent implements SamplingMessageContentBlock
{
    public string $type;

    /**
     * @param ContentBlock[] $content
     * @param array<string,mixed>|null $structuredContent
     */
    public function __construct(
        public string $toolUseId,
        public array $content,
        public ?array $structuredContent = null,
        public ?bool $isError = null,
        public ?array $_meta = null,
    ) {
        $this->type = 'tool_result';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'toolUseId' => $this->toolUseId,
            'content' => array_map(fn(ContentBlock $content) => $content->toArray(), $this->content),
        ];

        if(isset($this->structuredContent)) $results['structuredContent'] = $this->structuredContent ?: (object) [];
        if(isset($this->isError)) $results['isError'] = $this->isError;
        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
