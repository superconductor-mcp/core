<?php

namespace Fabricate\Schema\V20260728\Messaging;

use Fabricate\Schema\Common\Messaging\Result as BaseResult;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;

abstract readonly class Result extends BaseResult
{
    public function __construct(
        public string|ResultTypeEnum $resultType,
        ?ResultMetaObject $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray() : array
    {
        $results = parent::toArray();

        $results['resultType'] = $this->resultType instanceof ResultTypeEnum ? $this->resultType->value : $this->resultType;

        return $results;
    }
}
