<?php

namespace Fabricate\Schema\V20260728\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsRequest as ListRootsContract;
use Fabricate\Schema\V20260728\Concerns\InputRequest;
use Fabricate\Schema\V20260728\Messaging\Request;
use Fabricate\Schema\V20260728\MetaObject;

final readonly class ListRootsRequest extends Request implements ListRootsContract, InputRequest
{
    public function __construct(
        ?MetaObject $_meta = null,
    ) {
        $params = isset($_meta) ? ['_meta' => $_meta->toArray() ?: (object) []] : null;

        parent::__construct('roots/list', $params);
    }
}
