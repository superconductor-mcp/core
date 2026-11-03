<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\Implementation;
use Fabricate\Schema\V20251125\Capabilities\ClientCapabilities;

final readonly class InitializeRequestParams extends RequestParams
{
    public function __construct(
        public string $protocolVersion,
        public ClientCapabilities $capabilities,
        public Implementation $clientInfo,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['protocolVersion'] = $this->protocolVersion;
        $results['capabilities'] = $this->capabilities->toArray() ?: (object) [];
        $results['clientInfo'] = $this->clientInfo->toArray();

        return $results;
    }
}
