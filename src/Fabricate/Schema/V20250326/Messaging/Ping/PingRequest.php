<?php

namespace Fabricate\Schema\V20250326\Messaging\Ping;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Ping\PingRequest as PingContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250326\Messaging\Request;

final readonly class PingRequest extends Request implements PingContract, ClientRequest, ServerRequest
{
    public function __construct(
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('ping', null, $progress_token);
    }
}
