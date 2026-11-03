<?php

namespace Fabricate\Schema\V20241105\Messaging\Tools;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\ListToolsRequest as ListToolsContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20241105\Messaging\Pagination\PaginatedRequest;

final readonly class ListToolsRequest extends PaginatedRequest implements ListToolsContract, ClientRequest
{
    public function __construct(
        ?Cursor $cursor = null,
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('tools/list', $cursor, $progress_token);
    }
}
