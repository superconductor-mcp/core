<?php

namespace Fabricate\Schema\V20251125\Messaging\Autocomplete;

use Fabricate\Schema\V20251125\Concerns\PromptReference;
use Fabricate\Contracts\Schema\Messaging\Autocomplete\CompleteRequest as CompleteContract;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\Common\Messaging\RPC\JSONRPCRequest;
use Fabricate\Schema\V20251125\Messaging\Params\CompleteRequestParams;

final readonly class CompleteRequest extends JSONRPCRequest implements CompleteContract, ClientRequest
{
    public function __construct(
        RequestId $id,
        CompleteRequestParams $params,
    ) {
        parent::__construct("2.0", $id, 'completion/complete', $params);
    }

    public function referenceType(): string
    {
        return $this->params->ref->type;
    }

    public function referenceName(): string
    {
        return $this->params->ref instanceof PromptReference ? $this->params->ref->name : $this->params->ref->uri;
    }

    public function argumentName(): string
    {
        return $this->params->argument['name'];
    }

    public function argumentValue(): string
    {
        return $this->params->argument['value'];
    }

    public function contextArguments(): array
    {
        return (array) ($this->params->context['arguments'] ?? []);
    }
}
