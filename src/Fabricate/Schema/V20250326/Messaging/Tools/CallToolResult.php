<?php

namespace Fabricate\Schema\V20250326\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolResult as CallToolContract;
use Fabricate\Schema\V20250326\Concerns\AudioContent;
use Fabricate\Schema\V20250326\Concerns\EmbeddedResource;
use Fabricate\Schema\V20250326\Concerns\ImageContent;
use Fabricate\Schema\V20250326\Concerns\TextContent;
use Fabricate\Schema\V20250326\Messaging\Result;

final readonly class CallToolResult extends Result implements CallToolContract, ServerResult
{
    /**
     * @param (TextContent|ImageContent|AudioContent|EmbeddedResource)[] $content
     * @param bool|null $isError
     * @param array|null $_meta
     */
    public function __construct(
        public array $content,
        public ?bool $isError = null,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['content'] = array_map(fn(TextContent|ImageContent|AudioContent|EmbeddedResource $content) => $content->toArray(), $this->content);
        if(isset($this->isError)) $results['isError'] = $this->isError;

        return $results;
    }
}
