<?php

namespace Fabricate\Schema\V20251125\Messaging\Errors;

use Fabricate\Contracts\Schema\ElicitationErrorCode;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20251125\Messaging\MCPError;
use Fabricate\Schema\V20251125\Messaging\Params\ElicitRequestURLParams;
use Fabricate\Schema\V20251125\Messaging\RPC\JSONRPCErrorResponse;

readonly class URLElicitationRequiredError extends JSONRPCErrorResponse
{
    /**
     * @param ElicitRequestURLParams[] $elicitations
     * @param array<string,mixed> $data
     */
    public function __construct(
        ?RequestId $id,
        string $message,
        array $elicitations,
        array $data = [],
    ) {
        $data['elicitations'] = array_map(fn(ElicitRequestURLParams $elicitation) => $elicitation->toArray(), $elicitations);

        parent::__construct(
            "2.0",
            $id,
            new MCPError(ElicitationErrorCode::URL_ELICITATION_REQUIRED->value, $message, $data)
        );
    }
}
