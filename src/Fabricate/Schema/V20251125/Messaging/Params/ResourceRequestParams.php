<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;

abstract readonly class ResourceRequestParams extends RequestParams
{
    public function __construct(
        public string $uri,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['uri'] = $this->uri;

        return $results;
    }
}
