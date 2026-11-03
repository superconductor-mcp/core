<?php

namespace Fabricate\Schema\V20251125\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolRequest as CallToolContract;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\CallToolRequestParams;

final readonly class CallToolRequest extends JSONRPCRequest implements CallToolContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        CallToolRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'tools/call', $params);
    }

    public function name(): string
    {
        return $this->params->name;
    }

    public function arguments(): ?array
    {
        return $this->params->arguments;
    }

    public function runsAsTask(): bool
    {
        return isset($this->params->task);
    }

    public function taskTtl(): ?int
    {
        return $this->params->task?->ttl;
    }
}
