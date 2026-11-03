<?php

namespace Fabricate\Schema\V20260728\Messaging;

use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\EmptyResult as EmptyContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;

final readonly class EmptyResult extends Result implements EmptyContract, ClientResult, ServerResult
{
    public function __construct(
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($resultType, $_meta);
    }
}
