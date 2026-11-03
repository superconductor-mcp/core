<?php

namespace Fabricate\Schema\V20250326\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsRequest as ListRootsContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250326\Messaging\Request;

final readonly class ListRootsRequest extends Request implements ListRootsContract, ServerRequest
{
    public function __construct(
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('roots/list', null, $progress_token);
    }
}
