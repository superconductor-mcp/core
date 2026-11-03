<?php

namespace Fabricate\Schema\V20251125\Messaging\Initialize;

use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeResult as InitializeContract;
use Fabricate\Schema\V20251125\Implementation;
use Fabricate\Schema\V20251125\Messaging\Result;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20251125\Capabilities\ServerCapabilities;

final readonly class InitializeResult extends Result implements InitializeContract, ServerResult
{
    public function __construct(
        public string $protocolVersion,
        public ServerCapabilities $capabilities,
        public Implementation $serverInfo,
        public ?string $instructions = null,
        ?array $_meta = null
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['protocolVersion'] = $this->protocolVersion;
        $results['capabilities'] = $this->capabilities->toArray() ?: (object) [];
        $results['serverInfo'] = $this->serverInfo->toArray();
        if(isset($this->instructions)) $results['instructions'] = $this->instructions;

        return $results;
    }
}
