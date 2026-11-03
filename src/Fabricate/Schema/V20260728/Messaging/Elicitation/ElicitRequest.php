<?php

namespace Fabricate\Schema\V20260728\Messaging\Elicitation;

use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitRequest as ElicitContract;
use Fabricate\Schema\V20260728\Concerns\ElicitRequestParams;
use Fabricate\Schema\V20260728\Concerns\InputRequest;
use Fabricate\Schema\V20260728\Messaging\Request;

final readonly class ElicitRequest extends Request implements ElicitContract, InputRequest
{
    public function __construct(
        ElicitRequestParams $params,
    ) {
        parent::__construct('elicitation/create', $params);
    }
}
