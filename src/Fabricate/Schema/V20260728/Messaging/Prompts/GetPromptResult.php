<?php

namespace Fabricate\Schema\V20260728\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\Prompts\GetPromptResult as GetPromptContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Capabilities\Prompts\PromptMessage;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

final readonly class GetPromptResult extends Result implements GetPromptContract, ServerResult
{
    /**
     * @param PromptMessage[] $messages
     */
    public function __construct(
        public array $messages,
        public ?string $description = null,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->description)) $results['description'] = $this->description;
        $results['messages'] = array_map(fn(PromptMessage $message) => $message->toArray(), $this->messages);

        return $results;
    }
}
