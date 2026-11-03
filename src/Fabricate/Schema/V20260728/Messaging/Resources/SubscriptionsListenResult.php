<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\SubscriptionsListenResult as SubscriptionsListenResultContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\SubscriptionsListenResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

final readonly class SubscriptionsListenResult extends Result implements SubscriptionsListenResultContract, ServerResult
{
    public function __construct(
        SubscriptionsListenResultMetaObject $_meta,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
    ) {
        parent::__construct($resultType, $_meta);
    }
}
