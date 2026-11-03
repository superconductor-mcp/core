<?php

namespace Fabricate\Schema\V20260728\Messaging\Pagination;

use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\Messaging\Pagination\PaginatedRequest as PaginatedContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20260728\Messaging\Params\PaginatedRequestParams;

abstract readonly class PaginatedRequest extends JSONRPCRequest implements PaginatedContract
{
    public function __construct(
        RequestId $id,
        string $method,
        PaginatedRequestParams $params,
    ) {
        parent::__construct("2.0", $id, $method, $params);
    }

    public function cursor(): ?Cursor
    {
        return $this->params?->cursor;
    }
}
