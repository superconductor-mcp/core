<?php

namespace Fabricate\Schema\V20251125\Messaging;

use Fabricate\Schema\Common\Messaging\Result as BaseResult;

abstract readonly class Result extends BaseResult
{
    public function __construct(
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }
}
