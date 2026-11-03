<?php

namespace Fabricate\Schema\V20250326\Messaging\Resources;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ListResourceTemplatesRequest as ListTemplatesContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250326\Messaging\Pagination\PaginatedRequest;

final readonly class ListResourceTemplatesRequest extends PaginatedRequest implements ListTemplatesContract, ClientRequest
{
    public function __construct(
        ?Cursor $cursor = null,
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('resources/templates/list', $cursor, $progress_token);
    }
}
