<?php

namespace Fabricate\Client;

use Fabricate\Contracts\Client\Client;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Entity;
use Fabricate\Mechanism\RequestContext;

class MCPClient extends Entity implements Client
{
    public function __construct(
        ClientConfiguration $configuration,
        string $active_protocol_version,
        ?string $session_id = null,
    ) {
        parent::__construct($configuration, $active_protocol_version, $session_id);
    }

    public function receiveRequest(Request $request, ?RequestContext $context = null): Result|MCPError|Deferred
    {
        return $this->dispatch($request, $context);
    }

    public function receiveNotification(Notification $notification): void
    {

    }

    public function receiveResponse(Result|MCPError $response): void
    {

    }
}
