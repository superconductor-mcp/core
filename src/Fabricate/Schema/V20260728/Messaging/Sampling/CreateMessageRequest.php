<?php

namespace Fabricate\Schema\V20260728\Messaging\Sampling;

use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageRequest as CreateMessageContract;
use Fabricate\Schema\V20260728\Concerns\InputRequest;
use Fabricate\Schema\V20260728\Messaging\Params\CreateMessageRequestParams;
use Fabricate\Schema\V20260728\Messaging\Request;

final readonly class CreateMessageRequest extends Request implements CreateMessageContract, InputRequest
{
    public function __construct(
        CreateMessageRequestParams $params,
    ) {
        parent::__construct('sampling/createMessage', $params);
    }
}
