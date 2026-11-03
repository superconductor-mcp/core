<?php

namespace Fabricate\Schema\V20251125\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsRequest as ListPromptsContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20251125\Messaging\Pagination\PaginatedRequest;
use Fabricate\Schema\V20251125\Messaging\Params\PaginatedRequestParams;

final readonly class ListPromptsRequest extends PaginatedRequest implements ListPromptsContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        ?PaginatedRequestParams $params = null,
    ) {
        parent::__construct($id, 'prompts/list', $params);
    }
}
