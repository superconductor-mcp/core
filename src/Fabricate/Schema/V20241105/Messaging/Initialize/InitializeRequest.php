<?php

namespace Fabricate\Schema\V20241105\Messaging\Initialize;

use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeRequest as InitializeContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20241105\Implementation;
use Fabricate\Schema\V20241105\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Schema\V20241105\Capabilities\ClientCapabilities;

final readonly class InitializeRequest extends Request implements InitializeContract, ClientRequest
{
    private string $protocol_version;

    public function __construct(
        string $protocol_version,
        ClientCapabilities $capabilities,
        Implementation $client_info,
        ?ProgressToken $progress_token = null,
    ) {
        $this->protocol_version = $protocol_version;

        $params = [
            'protocolVersion' => $protocol_version,
            'capabilities' => $capabilities->toArray() ?: (object) [],
            'clientInfo' => $client_info->toArray(),
        ];
        parent::__construct(
            'initialize',
            $params,
            $progress_token,
        );
    }

    public function protocolVersion(): string
    {
        return $this->protocol_version;
    }
}
