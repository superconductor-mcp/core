<?php

namespace Fabricate\Client;

use ArrayObject;
use Closure;
use Fabricate\Client\Signals\NotificationReceived;
use Fabricate\Client\Signals\ResponseReceived;
use Fabricate\Client\Signals\RootsChanged;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Client\Client;
use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Fabricate\Contracts\Schema\Messaging\Inputs\InputResponseRequest;
use Fabricate\Contracts\Schema\Messaging\Cancellation\CancelledNotification;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\ProtocolVersion;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\ElicitationErrorCode;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Core\Rpc\Procedures\ClientCapabilityProcedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Entity;
use Fabricate\Mechanism\Envelope;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;
use Throwable;
use Voyager\NutsAndBolts\Str;
use WeakMap;

/**
 * One side of a session with a server, in the version the transport agreed on. Requests from the server are answered
 * by the client's procedures from the developer's Roots, Sampling and Elicitation. A response to a request the client
 * sent goes to the closure given with it, or out as a ResponseReceived signal when none was; every notification goes
 * out as NotificationReceived. On 2026-07-28 an InputRequiredResult is answered from the same concretes and the request
 * retried, so what reaches the closure or signal is the final answer. What is queued goes out through the transport.
 */
class MCPClient extends Entity implements Client
{
    /** @var WeakMap<object, ArrayObject<string, self>>|null The open clients on each signal dispatcher, by session id. */
    private static ?WeakMap $open = null;

    /** @var ArrayObject<string, self>|null The open clients this one is listed in, until it closes. */
    private ?ArrayObject $clients = null;

    /** @var array<string, Closure(Result|MCPError): void> Waiting on the response to a sent request, by json-encoded id. */
    private array $awaiting = [];

    /** @var array<string, InputResponseRequest> On 2026-07-28, sent requests a server may answer with input_required, by json-encoded id. */
    private array $retryable = [];

    /** @var array<string, true> On 2025-11-25, the URL elicitations the server started with this client and hasn't completed, by elicitationId. */
    private array $open_elicitations = [];

    /** Why this client closed, once it has; a request sent afterwards is answered with it. */
    private ?string $closed = null;

    public function __construct(
        ClientConfiguration $configuration,
        string $active_protocol_version,
        ?string $session_id = null,
    ) {
        parent::__construct($configuration, $active_protocol_version, $session_id);

        $this->listenForChanges();
    }

    /**
     * Sends a request; $then takes its result or error when the server answers.
     *
     * @param (Closure(Result|MCPError): void)|null $then
     */
    public function request(Request $request, ?RequestId $id = null, ?Closure $then = null): RequestId
    {
        // A closed client sends nothing: the session is over, and the caller hears why at once.
        if(! is_null($this->closed))
        {
            $id = Envelope::request($this->active_protocol_version, $request, $id)->id;
            $this->deliver($id, $request->method, $this->error(RPCErrorCode::INTERNAL_ERROR->value, $this->closed), $then);

            return $id;
        }

        $id = parent::request($request, $id);

        if($then) $this->awaiting[self::pendingKey($id)] = $then;
        if($request instanceof InputResponseRequest) $this->retryable[self::pendingKey($id)] = $request;

        return $id;
    }

    /**
     * Ends the client's part in the session: every request still in flight is answered with an error saying why, and
     * roots changes no longer reach it.
     */
    public function close(string $reason = 'The connection closed.'): void
    {
        $this->closed = $reason;
        unset($this->clients[$this->session_id]);
        $this->clients = null;
        $this->failPending($reason);
    }

    /**
     * Answers every request still in flight with an internal error carrying $reason, as if the server had refused it:
     * its closure gets the error, or ResponseReceived fires with it. Nothing waits on those requests afterwards.
     */
    public function failPending(string $reason): void
    {
        foreach(array_keys($this->pending_requests) as $key) $this->fail(new RequestId(json_decode($key)), $reason);
    }

    /**
     * Answers one request in flight with an internal error carrying $reason: the transport could not get it answered.
     * A request already answered, or unknown, is left alone.
     */
    public function fail(RequestId $id, string $reason): void
    {
        $key = self::pendingKey($id);
        $then = $this->awaiting[$key] ?? null;
        unset($this->awaiting[$key], $this->retryable[$key]);

        if(is_null($method = $this->forget($id))) return;

        $this->deliver($id, $method, $this->error(RPCErrorCode::INTERNAL_ERROR->value, $reason), $then);
    }

    /**
     * Nothing waits on a request that timed out: its closure is dropped with it.
     */
    protected function timedOut(RequestId $id): void
    {
        unset($this->awaiting[self::pendingKey($id)], $this->retryable[self::pendingKey($id)]);

        parent::timedOut($id);
    }

    public function receiveRequest(Request $request, ?RequestContext $context = null): Result|MCPError|Deferred
    {
        if($request instanceof V20251125\Messaging\Elicitation\ElicitRequest && $request->params instanceof V20251125\Messaging\Params\ElicitRequestURLParams) {
            $this->open_elicitations[$request->params->elicitationId] = true;
        }

        return $this->dispatch($request, $context);
    }

    /**
     * notifications/cancelled stops whatever is still to be sent for the server request it names. On 2025-11-25
     * notifications/elicitation/complete is ignored unless it names a URL elicitation still open with this client, as
     * the elicitation page requires. The rest, cancellations included, go out as NotificationReceived.
     */
    public function receiveNotification(Notification $notification): void
    {
        if($notification instanceof CancelledNotification) {
            $request_id = $notification->toArray()['params']['requestId'] ?? null;

            // 2025-11-25 lets requestId be left out, for tasks, which tasks/cancel stops instead.
            if(is_string($request_id) || is_int($request_id)) $this->cancel(new RequestId($request_id));
        }

        if($notification instanceof V20251125\Messaging\Elicitation\ElicitationCompleteNotification) {
            $id = $notification->toArray()['params']['elicitationId'];

            if(! isset($this->open_elicitations[$id])) return;

            unset($this->open_elicitations[$id]);
        }

        signal(new NotificationReceived($this->session_id, $notification));
    }

    public function receiveResponse(RequestId $id, string $method, Result|MCPError $response): void
    {
        $key = self::pendingKey($id);
        [$then, $original] = [$this->awaiting[$key] ?? null, $this->retryable[$key] ?? null];
        unset($this->awaiting[$key], $this->retryable[$key]);

        // URLElicitationRequiredError names the URL elicitations the server will report complete.
        if($response instanceof MCPError && $response->toArray()['code'] === ElicitationErrorCode::URL_ELICITATION_REQUIRED->value) {
            foreach($response->toArray()['data']['elicitations'] ?? [] as $elicitation) {
                if(is_string($elicitation['elicitationId'] ?? null)) $this->open_elicitations[$elicitation['elicitationId']] = true;
            }
        }

        if($response instanceof InputRequiredResult && $original) {
            $this->answerInputs($original, $response, $id, $method, $then);
            return;
        }

        $this->deliver($id, $method, $response, $then);
    }

    /**
     * Fulfils every input the server asked for from the developer's concretes, then retries the request under a new id
     * with the answers and the server's requestState unchanged. An input the client can't give ends the request with
     * that error instead.
     *
     * @param (Closure(Result|MCPError): void)|null $then
     */
    private function answerInputs(InputResponseRequest $original, InputRequiredResult $asked, RequestId $id, string $method, ?Closure $then): void
    {
        $requests = $asked->inputRequests->requests ?? [];
        $answers = [];
        $failed = false;

        $settle = function(string $key, Result|InputResponse|MCPError $answer) use (&$answers, &$failed, $requests, $original, $asked, $id, $method, $then): void {
            if($failed) return;

            if($answer instanceof MCPError) {
                $failed = true;
                $this->deliver($id, $method, $answer, $then);
                return;
            }

            $answers[$key] = $answer;

            if(count($answers) === count($requests)) {
                $this->request($original->retry(new RequestId((string) Str::uuid()), new InputResponses($answers), $asked->requestState), then: $then);
            }
        };

        if($requests === []) {
            // Nothing to fulfil: the spec lets the client retry at once.
            $this->request($original->retry(new RequestId((string) Str::uuid()), new InputResponses(), $asked->requestState), then: $then);
            return;
        }

        foreach($requests as $key => $request)
        {
            if($none = ClientCapabilityProcedure::withoutConcrete($request, $this->configuration, $this->active_protocol_version)) {
                $settle($key, $none);
                continue;
            }

            try {
                $concrete = ClientCapabilityProcedure::concrete($request, $this->configuration, $this->active_protocol_version);
            } catch(SuperconductorMCPException $e) {
                $settle($key, new V20260728\Messaging\MCPError(RPCErrorCode::tryFrom($e->getCode()) ? $e->getCode() : RPCErrorCode::INTERNAL_ERROR->value, $e->getMessage()));
                return;
            }

            ClientCapabilityProcedure::run($concrete, $request, $this->configuration, $this->active_protocol_version, null, fn(Result|InputResponse|MCPError $answer) => $settle($key, $answer), true);
        }
    }

    /**
     * @param (Closure(Result|MCPError): void)|null $then
     */
    private function deliver(RequestId $id, string $method, Result|MCPError $response, ?Closure $then): void
    {
        $then ? $then($response) : signal(new ResponseReceived($this->session_id, $id, $method, $response));
    }

    /**
     * Sends notifications/roots/list_changed when the developer's roots change, if this client declared
     * roots.listChanged and its version has the notification. Each dispatcher gets one listener, which walks the
     * clients still open on it; close() takes a client off.
     */
    private function listenForChanges(): void
    {
        if(! app()->isBound('signals')) return;

        $signals = app('signals');
        self::$open ??= new WeakMap();

        if(! isset(self::$open[$signals]))
        {
            $clients = self::$open[$signals] = new ArrayObject();

            $signals->listen(RootsChanged::class, function() use ($clients) {
                foreach($clients->getArrayCopy() as $client) $client->rootsChanged();
            });
        }

        $this->clients = self::$open[$signals];
        $this->clients[$this->session_id] = $this;
    }

    private function rootsChanged(): void
    {
        if(! $this->configuration->capabilities($this->active_protocol_version)->offersCapability('roots.listChanged')) return;

        $notification = match(ProtocolVersion::from($this->active_protocol_version)) {
            ProtocolVersion::V20241105 => new V20241105\Messaging\Roots\RootsListChangedNotification(),
            ProtocolVersion::V20250326 => new V20250326\Messaging\Roots\RootsListChangedNotification(),
            ProtocolVersion::V20250618 => new V20250618\Messaging\Roots\RootsListChangedNotification(),
            ProtocolVersion::V20251125 => new V20251125\Messaging\Roots\RootsListChangedNotification(),
            // 2026-07-28 has no roots list_changed notification.
            ProtocolVersion::V20260728 => null,
        };

        if($notification) $this->notify($notification);
    }
}
