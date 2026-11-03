<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class RequestParams implements Arrayable
{
    public ?array $_meta;

    public function __construct(
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        if(isset($progress_token))
        {
            $_meta ??= [];
            $_meta['progressToken'] = $progress_token->token;
        }

        $this->_meta = $_meta;
    }

    public function toArray(): array
    {
        $results = [];

        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
