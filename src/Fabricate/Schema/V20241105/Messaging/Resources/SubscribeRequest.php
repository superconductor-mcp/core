<?php

namespace Fabricate\Schema\V20241105\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscribeRequest as SubscribeContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20241105\Messaging\Request;

final readonly class SubscribeRequest extends Request implements SubscribeContract, ClientRequest
{
    public function __construct(
        string $uri,
        ?ProgressToken $progress_token = null,
    ){
        parent::__construct('resources/subscribe', ['uri' => $uri], $progress_token);
    }

    public function uri(): string
    {
        return $this->params['uri'];
    }
}
