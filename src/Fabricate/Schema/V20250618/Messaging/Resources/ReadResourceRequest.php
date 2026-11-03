<?php

namespace Fabricate\Schema\V20250618\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceRequest as ReadResourceContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Messaging\Request;

final readonly class ReadResourceRequest extends Request implements ReadResourceContract, ClientRequest
{
    public function __construct(
        string $uri,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null,
    ) {
        parent::__construct(
            "resources/read",
            ['uri' => $uri],
            $_meta,
            $progress_token
        );
    }

    public function uri(): string
    {
        return $this->params['uri'];
    }
}
