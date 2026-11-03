<?php

namespace Fabricate\Schema\V20250618\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolResult as CallToolContract;
use Fabricate\Schema\V20250618\Concerns\ContentBlock;
use Fabricate\Schema\V20250618\Messaging\Result;

final readonly class CallToolResult extends Result implements CallToolContract, ServerResult
{
    /**
     * @param ContentBlock[] $content
     * @param array<string,mixed>|null $structuredContent
     * @param bool|null $isError
     * @param array|null $_meta
     */
    public function __construct(
        public array $content,
        public ?array $structuredContent = null,
        public ?bool $isError = null,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['content'] = array_map(fn(ContentBlock $content) => $content->toArray(), $this->content);
        if(isset($this->structuredContent)) $results['structuredContent'] = $this->structuredContent ?: (object) [];
        if(isset($this->isError)) $results['isError'] = $this->isError;

        return $results;
    }
}
