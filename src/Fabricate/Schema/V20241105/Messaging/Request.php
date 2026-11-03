<?php

namespace Fabricate\Schema\V20241105\Messaging;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\Common\Messaging\Request as BaseRequest;

readonly class Request extends BaseRequest
{
    public function __construct(
        string $method,
        ?array $params = null,
        ?ProgressToken $progress_token = null,
    ) {
        if(isset($params) || isset($progress_token))
        {
            $payload = $params ?? [];

            if(isset($progress_token))
            {
                $payload['_meta'] = [
                    'progressToken' => $progress_token->token
                ];
            }

            $params = $payload;
        }

        parent::__construct($method, $params);
    }
}
