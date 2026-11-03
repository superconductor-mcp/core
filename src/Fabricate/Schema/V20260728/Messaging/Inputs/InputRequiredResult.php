<?php

namespace Fabricate\Schema\V20260728\Messaging\Inputs;

use Fabricate\Contracts\Schema\Messaging\Inputs\InputRequiredResult as InputRequiredResultContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

final readonly class InputRequiredResult extends Result implements InputRequiredResultContract, ServerResult
{
    /**
     * @param string|ResultTypeEnum $resultType
     * @param InputRequests|null $inputRequests
     * @param string|null $requestState
     * @param ResultMetaObject|null $_meta
     */
    public function __construct(
        string|ResultTypeEnum $resultType = ResultTypeEnum::INPUT_REQUIRED,
        public ?InputRequests $inputRequests = null,
        public ?string $requestState = null,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        if(isset($this->inputRequests)) $results['inputRequests'] = $this->inputRequests->toArray() ?: (object) [];
        if(isset($this->requestState)) $results['requestState'] = $this->requestState;

        return $results;
    }
}
