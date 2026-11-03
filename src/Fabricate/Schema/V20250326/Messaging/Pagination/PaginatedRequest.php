<?php

namespace Fabricate\Schema\V20250326\Messaging\Pagination;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Pagination\PaginatedRequest as PaginatedContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250326\Messaging\Request;

abstract readonly class PaginatedRequest extends Request implements PaginatedContract
{
    private ?Cursor $cursor;

    public function __construct(
        string $method,
        ?Cursor $cursor = null,
        ?ProgressToken $progress_token = null,
    ) {
        $this->cursor = $cursor;

        $params = isset($cursor) ? ['cursor' => $cursor->pointer] : null;

        parent::__construct($method, $params, $progress_token);
    }

    public function cursor(): ?Cursor
    {
        return $this->cursor;
    }
}
