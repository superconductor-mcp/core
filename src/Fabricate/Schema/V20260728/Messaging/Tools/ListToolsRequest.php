<?php

namespace Fabricate\Schema\V20260728\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\ListToolsRequest as ListToolsContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedRequest;
use Fabricate\Schema\V20260728\Messaging\Params\PaginatedRequestParams;

final readonly class ListToolsRequest extends PaginatedRequest implements ListToolsContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        PaginatedRequestParams $params,
    ) {
        parent::__construct($id, 'tools/list', $params);
    }
}
