<?php

namespace Fabricate\Schema\V20260728\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolResult as CallToolContract;
use Fabricate\Schema\V20260728\Concerns\ContentBlock;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

final readonly class CallToolResult extends Result implements CallToolContract, ServerResult
{
    /**
     * @param ContentBlock[] $content
     * @param mixed $structuredContent
     * @param bool|null $isError
     * @param string|ResultTypeEnum $resultType
     * @param ResultMetaObject|null $_meta
     */
    public function __construct(
        public array $content,
        public mixed $structuredContent = null,
        public ?bool $isError = null,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['content'] = array_map(fn(ContentBlock $content) => $content->toArray(), $this->content);
        if(isset($this->structuredContent)) $results['structuredContent'] = $this->structuredContent;
        if(isset($this->isError)) $results['isError'] = $this->isError;

        return $results;
    }
}
