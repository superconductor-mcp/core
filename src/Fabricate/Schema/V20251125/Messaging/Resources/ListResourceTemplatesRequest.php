<?php

namespace Fabricate\Schema\V20251125\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourceTemplatesRequest as ListTemplatesContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedRequest;
use Fabricate\Schema\V20251125\Messaging\Params\PaginatedRequestParams;

final readonly class ListResourceTemplatesRequest extends PaginatedRequest implements ListTemplatesContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        ?PaginatedRequestParams $params = null,
    ) {
        parent::__construct($id, 'resources/templates/list', $params);
    }
}
