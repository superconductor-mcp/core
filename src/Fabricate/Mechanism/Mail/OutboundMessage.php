<?php

namespace Fabricate\Mechanism\Mail;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCMessage;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Mechanism\Envelope;

/**
 * Mail posted to the event loop when work finishes outside the turn that started it: the answer to a request,
 * or a notification. The transport running the session puts message() on its entity's queue.
 */
final readonly class OutboundMessage
{
    public function __construct(
        public string $session_id,
        public string $protocol_version,
        public Result|MCPError|Notification $payload,
        public ?RequestId $id = null,
        /** The request a notification was sent while answering, if any: transports that stream per request send it there. */
        public ?RequestId $related_request = null,
    ) {
        if(! $payload instanceof Notification && is_null($id)) {
            throw new SuperconductorMCPException('An answer to a request needs the request\'s id.');
        }
    }

    /**
     * The payload wrapped in its protocol version's JSON-RPC class, ready for the queue.
     */
    public function message(): JSONRPCMessage
    {
        return $this->payload instanceof Notification
            ? Envelope::notification($this->protocol_version, $this->payload)
            : Envelope::response($this->protocol_version, $this->id, $this->payload);
    }
}
