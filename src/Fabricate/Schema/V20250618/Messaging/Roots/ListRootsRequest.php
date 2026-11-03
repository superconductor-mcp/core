<?php

namespace Fabricate\Schema\V20250618\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsRequest as ListRootsContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Messaging\Request;

final readonly class ListRootsRequest extends Request implements ListRootsContract, ServerRequest
{
    public function __construct(
        ?array $_meta = null,
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('roots/list', null, $_meta, $progress_token);
    }
}
