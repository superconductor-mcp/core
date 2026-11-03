<?php

namespace Fabricate\Schema\V20260728\Messaging\NutsAndBolts;

use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;

readonly class ResultType
{
    public function __construct(
        public string|ResultTypeEnum $resultType,
    ) {

    }
}
