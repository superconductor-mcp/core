<?php

namespace Fabricate\Schema\V20260728\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourcesRequest as ListResourcesContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedRequest;
use Fabricate\Schema\V20260728\Messaging\Params\PaginatedRequestParams;

final readonly class ListResourcesRequest extends PaginatedRequest implements ListResourcesContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        PaginatedRequestParams $params,
    ) {
        parent::__construct($id, 'resources/list', $params);
    }
}
