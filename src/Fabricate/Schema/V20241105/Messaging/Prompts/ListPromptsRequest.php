<?php

namespace Fabricate\Schema\V20241105\Messaging\Prompts;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\ListPromptsRequest as ListPromptsContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20241105\Messaging\Pagination\PaginatedRequest;

final readonly class ListPromptsRequest extends PaginatedRequest implements ListPromptsContract, ClientRequest
{
    public function __construct(
        ?Cursor $cursor = null,
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct('prompts/list', $cursor, $progress_token);
    }
}
