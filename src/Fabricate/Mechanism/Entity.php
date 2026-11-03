<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Mechanism\Entity as EntityContract;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\Messaging\Tasks\CreateTaskResult;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCMessage;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Core\Routing\ProcedureRouterFacade;
use Fabricate\Mechanism\Decoding\DecodedError;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Decoding\DecodedResponse;
use Fabricate\Schema\V20260728;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Voyager\NutsAndBolts\Str;

abstract class Entity implements EntityContract
{
    /** @var array<string, string> The method of each request this entity sent and still awaits an answer to, by json-encoded id. */
    protected array $pending_requests = [];

    /** @var list<JSONRPCMessage> Messages waiting for the transport to send them, oldest first. */
    protected array $messages_queued = [];

    /** The method of the sent request the response being received answers, while it is received. */
    private ?string $answered_method = null;

    /** @var array<string, Cancellation> The requests whose answers are still coming, by json-encoded id. */
    protected array $answering = [];

    /** Identifies this entity's session: in signal names, and on mail its async work posts back to the loop. */
    public readonly string $session_id;

    public function __construct(
        public readonly EntityConfiguration $configuration,
        public readonly string $active_protocol_version,
        ?string $session_id = null,
        /** Who sent the messages, as the transport authenticated them; null when it didn't. Tasks belong to it. */
        public readonly ?string $auth_identifier = null,
    ) {
        $this->session_id = $session_id ?? (string) Str::uuid();
    }

    /**
     * Queues a message for the transport to send on its next drain.
     */
    public function queue(JSONRPCMessage $message): void
    {
        $this->messages_queued[] = $message;
    }

    /**
     * Whether anything is waiting to be sent, without taking it.
     */
    public function hasQueued(): bool
    {
        return $this->messages_queued !== [];
    }

    /**
     * Hands over every queued message, oldest first, and empties the queue.
     *
     * @return list<JSONRPCMessage>
     */
    public function pullQueued(): array
    {
        [$messages, $this->messages_queued] = [$this->messages_queued, []];

        return $messages;
    }

    abstract public function receiveRequest(Request $request, ?RequestContext $context = null): Result|MCPError|Deferred;

    abstract public function receiveNotification(Notification $notification): void;

    /**
     * A response to a request this entity sent: that request's id and method, and the result or error.
     */
    abstract public function receiveResponse(RequestId $id, string $method, Result|MCPError $response): void;

    /**
     * Handles one message from the peer. A request comes back as the JSON-RPC response to send, unless its answer comes
     * as mail: answered later, or after notifications it sent. Notifications and responses are never answered, so
     * they come back as null.
     */
    public function receiveMessage(DecodedMessage $message): ?JSONRPCMessage
    {
        $reconciled = $this->reconcileMessage($message);

        if($message instanceof DecodedRequest)
        {
            $token = $message->params['_meta']['progressToken'] ?? null;
            $log_level = $message->params['_meta']['io.modelcontextprotocol/logLevel'] ?? null;
            $context = new RequestContext($this->session_id, $message->id(), $this->active_protocol_version, is_string($token) || is_int($token) ? new ProgressToken($token) : null, log_level: is_string($log_level) ? LoggingLevel::tryFrom($log_level) : null, client_capabilities: (array) ($message->params['_meta']['io.modelcontextprotocol/clientCapabilities'] ?? []), auth_identifier: $this->auth_identifier);
            $answer = $reconciled instanceof Request ? $this->receiveRequest($reconciled, $context) : $reconciled;

            // An async answer arrives later as mail, so nothing is sent for the request now; until then it can be cancelled.
            if($answer instanceof Deferred)
            {
                $this->answering = array_filter($this->answering, fn(Cancellation $cancellation) => ! $cancellation->answered);
                $this->answering[json_encode($message->id()->id)] = $context->cancellation;

                return null;
            }

            // Log and progress notifications sent while answering went out as mail; the answer follows them the same way,
            // never ahead. A task's handle is the exception: what its work sends is about the task, so the handle goes first.
            if($context->cancellation->notified && ! $answer instanceof CreateTaskResult)
            {
                $context->reply($answer);

                return null;
            }

            return $this->encodeMessage($message->id(), $answer);
        }

        if(($message instanceof DecodedResponse || $message instanceof DecodedError) && ! is_null($message->id()))
        {
            // A response answering no request of this entity's was refused by hydration, and has no method.
            [$method, $this->answered_method] = [$this->answered_method, null];

            if(isset($method)) $this->receiveResponse($message->id(), $method, $reconciled);
        }
        elseif($reconciled instanceof Notification)
        {
            $this->receiveNotification($reconciled);
        }

        return null;
    }

    /**
     * Cancels a request whose answer is still coming: nothing more is sent for it. A request already answered, or
     * unknown, is left alone.
     */
    public function cancel(RequestId $request): void
    {
        $key = json_encode($request->id);

        ($this->answering[$key] ?? null)?->cancel();
        unset($this->answering[$key]);
    }

    /**
     * Sends a request: wraps it in this version's JSON-RPC request and queues it, remembering its method so the
     * response can be hydrated. From 2025-11-25 a request carries its own id; earlier versions take $id here.
     */
    public function request(Request $request, ?RequestId $id = null): RequestId
    {
        $message = Envelope::request($this->active_protocol_version, $request, $id);
        $this->pending_requests[self::pendingKey($message->id)] = $request->method;
        $this->queue($message);

        return $message->id;
    }

    /**
     * Sends a notification in this version's JSON-RPC envelope.
     */
    public function notify(Notification $notification): void
    {
        $this->queue(Envelope::notification($this->active_protocol_version, $notification));
    }

    /**
     * One key for an int and a string id that print the same.
     */
    protected static function pendingKey(?RequestId $id): string
    {
        return json_encode($id?->id);
    }

    /**
     * The schema object the message is in this entity's protocol version, or an MCPError saying why it is none.
     */
    protected function reconcileMessage(DecodedMessage $message): Result|Notification|Request|MCPError
    {
        if($unsupported = $this->unsupportedVersion($message)) return $unsupported;

        $request_method = null;

        if($message instanceof DecodedResponse || $message instanceof DecodedError)
        {
            $request_method = $this->answered_method = $this->pending_requests[self::pendingKey($message->id())] ?? null;
            unset($this->pending_requests[self::pendingKey($message->id())]);
        }

        return MessageHydrator::hydrateAs($message, $this->active_protocol_version, $request_method);
    }

    /**
     * On 2026-07-28 every request names its protocol version in _meta. One this entity doesn't speak is answered with
     * UnsupportedProtocolVersionError, listing the modern versions it does; a missing or malformed one is left to
     * hydration, which refuses it as invalid params.
     */
    private function unsupportedVersion(DecodedMessage $message): ?MCPError
    {
        if($this->active_protocol_version !== '2026-07-28' || ! $message instanceof DecodedRequest) return null;

        $requested = $message->params['_meta']['io.modelcontextprotocol/protocolVersion'] ?? null;

        if(! is_string($requested) || $requested === $this->active_protocol_version) return null;

        $supported = array_values(array_intersect($this->configuration->supportedProtocolVersions(), ['2026-07-28']));

        return new V20260728\Messaging\MCPError(MCPErrorCode::UNSUPPORTED_PROTOCOL_VERSION->value, "Unsupported protocol version: [{$requested}].", ['supported' => $supported, 'requested' => $requested]);
    }

    /**
     * Wraps a request's answer in this entity's protocol version's JSON-RPC response.
     */
    protected function encodeMessage(RequestId $id, Result|MCPError $answer): JSONRPCMessage
    {
        return Envelope::response($this->active_protocol_version, $id, $answer);
    }

    /**
     * Routes a request to its procedure in this entity's protocol version. The router refuses by throwing,
     * and its code and message become the error.
     */
    protected function dispatch(Request $request, ?RequestContext $context = null): Result|MCPError|Deferred
    {
        try {
            return ProcedureRouterFacade::dispatch($this->active_protocol_version, $request, $this->configuration, $context);
        } catch (SuperconductorMCPException $e) {
            return new V20260728\Messaging\MCPError($e->getCode(), $e->getMessage());
        }
    }
}
