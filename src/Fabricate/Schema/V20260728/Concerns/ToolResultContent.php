<?php

namespace Fabricate\Schema\V20260728\Concerns;

use Fabricate\Schema\V20260728\MetaObject;

readonly class ToolResultContent implements SamplingMessageContentBlock
{
    public string $type;

    /**
     * @param ContentBlock[] $content
     */
    public function __construct(
        public string $toolUseId,
        public array $content,
        public mixed $structuredContent = null,
        public ?bool $isError = null,
        public ?MetaObject $_meta = null,
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

        if(isset($this->structuredContent)) $results['structuredContent'] = $this->structuredContent;
        if(isset($this->isError)) $results['isError'] = $this->isError;
        if(isset($this->_meta)) $results['_meta'] = $this->_meta->toArray() ?: (object) [];

        return $results;
    }
}
