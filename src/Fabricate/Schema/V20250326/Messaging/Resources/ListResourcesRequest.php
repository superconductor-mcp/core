<?php

namespace Fabricate\Schema\V20250326\Messaging\Resources;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourcesRequest as ListResourcesContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250326\Messaging\Pagination\PaginatedRequest;

final readonly class ListResourcesRequest extends PaginatedRequest implements ListResourcesContract, ClientRequest
{
    public function __construct(
        ?Cursor $cursor = null,
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('resources/list', $cursor, $progress_token);
    }
}
