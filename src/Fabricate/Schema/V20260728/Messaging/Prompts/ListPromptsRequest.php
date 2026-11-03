<?php

namespace Fabricate\Schema\V20260728\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsRequest as ListPromptsContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20260728\Messaging\Pagination\PaginatedRequest;
use Fabricate\Schema\V20260728\Messaging\Params\PaginatedRequestParams;

final readonly class ListPromptsRequest extends PaginatedRequest implements ListPromptsContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        PaginatedRequestParams $params,
    ) {
        parent::__construct($id, 'prompts/list', $params);
    }
}
