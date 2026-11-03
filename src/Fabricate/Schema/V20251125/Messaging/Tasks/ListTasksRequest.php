<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\ListTasksRequest as ListTasksRequestContract;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedRequest;
use Fabricate\Schema\V20251125\Messaging\Params\PaginatedRequestParams;

final readonly class ListTasksRequest extends PaginatedRequest implements ListTasksRequestContract, ClientRequest, ServerRequest
{
    public function __construct(
        RequestId $id,
        ?PaginatedRequestParams $params = null,
    ) {
        parent::__construct($id, 'tasks/list', $params);
    }
}
