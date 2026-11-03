<?php

namespace Fabricate\Schema\V20251125\Messaging\Tasks;

use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskPayloadResult as GetTaskPayloadResultContract;
use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Messaging\Result;

final readonly class GetTaskPayloadResult extends Result implements GetTaskPayloadResultContract, ClientResult, ServerResult
{
    /**
     * @param array<string,mixed> $payload
     * @param array|null $_meta
     */
    public function __construct(
        public array $payload,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        return parent::toArray() + $this->payload;
    }
}
