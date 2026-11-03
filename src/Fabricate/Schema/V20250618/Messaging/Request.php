<?php

namespace Fabricate\Schema\V20250618\Messaging;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\Common\Messaging\Request as BaseRequest;

readonly class Request extends BaseRequest
{
    public function __construct(
        string $method,
        ?array $params = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null,
    ) {
        if(isset($params) || isset($_meta) || isset($progress_token))
        {
            $payload = $params ?? [];

            if(isset($_meta) || isset($progress_token))
            {
                $meta = $_meta ?? [];
                if(isset($progress_token)) $meta['progressToken'] = $progress_token->token;

                $payload['_meta'] = $meta ?: (object) [];
            }

            $params = $payload;
        }

        parent::__construct($method, $params);
    }
}
