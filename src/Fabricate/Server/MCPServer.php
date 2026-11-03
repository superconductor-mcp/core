<?php

namespace Fabricate\Server;

use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCMessage;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\Server;
use Fabricate\Mechanism\Decoding\DecodedRequest;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Entity;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Contracts\Schema\Messaging\Cancellation\CancelledNotification;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Mechanism\Signals\ListChanged;
use Fabricate\Mechanism\Signals\LogMessage;
use Fabricate\Schema\V20251125;
use Fabricate\Mechanism\Signals\ResourceUpdated;
use Voyager\Contracts\IOPools\Loop;
use ArrayObject;
use WeakMap;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Schema\V20260728\Messaging\MCPError as SessionError;

class MCPServer extends Entity implements Server
{
    /** @var WeakMap<object, ArrayObject<string, self>>|null The open sessions on each signal dispatcher, by session id. */
    private static ?WeakMap $open = null;

    /** @var ArrayObject<string, self>|null The open sessions this one is listed in, until it closes. */
    private ?ArrayObject $sessions = null;

    public function __construct(
        ServerConfiguration $configuration,
        string $active_protocol_version,
        ?string $session_id = null,
    ) {
        parent::__construct($configuration, $active_protocol_version, $session_id);

        $this->listenForChanges();
    }

    /**
     * Ends the session: change signals no longer reach it, and nothing the dispatcher holds keeps it alive.
     */
    public function close(): void
    {
        unset($this->sessions[$this->session_id]);
        $this->sessions = null;
    }

    /**
     * Sends notifications/resources/updated when a resource this session subscribed to changes, and a list_changed
     * notification when a list this configuration declared listChanged for changes. Both go out as mail, so they
     * wake the transport even when nothing else is happening.
     *
     * Each dispatcher gets one listener per signal, which walks the sessions still open on it; a session lists itself
     * here and close() takes it off, so ended sessions leave nothing behind however long the process runs.
     */
    private function listenForChanges(): void
    {
        if(! app()->isBound('signals')) return;

        $signals = app('signals');
        self::$open ??= new WeakMap();

        if(! isset(self::$open[$signals]))
        {
            $sessions = self::$open[$signals] = new ArrayObject();
            $reached = fn(?string $session_id): array => is_null($session_id)
                ? $sessions->getArrayCopy()
                : array_filter([$sessions[$session_id] ?? null]);

            $signals->listen(ResourceUpdated::class, function(ResourceUpdated $signal) use ($reached) {
                foreach($reached($signal->session_id) as $server) $server->resourceChanged($signal->uri);
            });

            $signals->listen(ListChanged::class, function(ListChanged $signal) use ($reached) {
                foreach($reached($signal->session_id) as $server) $server->listChangedFor($signal->capability);
            });

            $signals->listen(LogMessage::class, function(LogMessage $signal) use ($reached) {
                foreach($reached($signal->session_id) as $server) $server->logged($signal);
            });
        }

        $this->sessions = self::$open[$signals];
        $this->sessions[$this->session_id] = $this;
    }

    private function resourceChanged(string $uri): void
    {
        if($this->configuration->subscribedTo($uri)) $this->post($this->resourceUpdated($uri));
    }

    /**
     * Sends a log message tied to the request it was logged while answering, if the configuration declares logging
     * and the message is at or above the level the client set. Before the client sets one, every level goes out.
     */
    private function logged(LogMessage $signal): void
    {
        if(! $this->configuration->capabilities($this->active_protocol_version)->offersCapability('logging')) return;

        // 2026-07-28 has no setLevel: a request that names no level gets no log messages.
        if($this->active_protocol_version === '2026-07-28') {
            if(is_null($signal->requested_level) || ! $signal->level->atLeast($signal->requested_level)) return;
        } else {
            $threshold = $this->configuration->logLevel();

            if($threshold && ! $signal->level->atLeast($threshold)) return;
        }

        $notification = in_array($this->active_protocol_version, ['2025-11-25', '2026-07-28'], true)
            ? new ($this->schema('Messaging\\Logging\\LoggingMessageNotification'))(new ($this->schema('Messaging\\Params\\LoggingMessageNotificationParams'))($signal->level, $signal->data, $signal->logger))
            : new ($this->schema('Messaging\\Logging\\LoggingMessageNotification'))($signal->level, $signal->data, $signal->logger);

        app(Loop::class)->post(new OutboundMessage($this->session_id, $this->active_protocol_version, $notification, related_request: $signal->request));
    }

    private function listChangedFor(string $capability): void
    {
        if($this->configuration->announcesListChanges($capability)) $this->post($this->listChanged($capability));
    }

    private function post(Notification $notification): void
    {
        app(Loop::class)->post(new OutboundMessage($this->session_id, $this->active_protocol_version, $notification));
    }

    private function resourceUpdated(string $uri): Notification
    {
        $class = $this->schema('Messaging\\Resources\\ResourceUpdatedNotification');

        return in_array($this->active_protocol_version, ['2024-11-05', '2025-03-26', '2025-06-18'], true)
            ? new $class($uri)
            : new $class(new ($this->schema('Messaging\\Params\\ResourceUpdatedNotificationParams'))($uri));
    }

    private function listChanged(string $capability): Notification
    {
        return new ($this->schema(match($capability) {
            ListChanged::TOOLS => 'Messaging\\Tools\\ToolListChangedNotification',
            ListChanged::PROMPTS => 'Messaging\\Prompts\\PromptListChangedNotification',
            ListChanged::RESOURCES => 'Messaging\\Resources\\ResourceListChangedNotification',
        }))();
    }

    /**
     * @return class-string
     */
    private function schema(string $class): string
    {
        return 'Fabricate\\Schema\\V'.str_replace('-', '', $this->active_protocol_version).'\\'.$class;
    }

    /**
     * Refuses a repeated initialize before hydrating it: an initialize naming another version would otherwise fail as an unsupported version.
     */
    public function receiveMessage(DecodedMessage $message): ?JSONRPCMessage
    {
        // 2026-07-28 has no initialize, so the router answers it as an unknown method.
        if($message instanceof DecodedRequest && $message->method === 'initialize' && $this->active_protocol_version !== '2026-07-28')
        {
            return $this->encodeMessage($message->id(), new SessionError(RPCErrorCode::INVALID_REQUEST->value, 'Session already initialized: [initialize] can only be sent once.'));
        }

        return parent::receiveMessage($message);
    }

    public function receiveRequest(Request $request, ?RequestContext $context = null): Result|MCPError|Deferred
    {
        return $this->dispatch($request, $context);
    }

    /**
     * notifications/cancelled stops whatever is still to be sent for the request it names. The other notifications a
     * client sends need nothing from the server.
     */
    public function receiveNotification(Notification $notification): void
    {
        if(! $notification instanceof CancelledNotification) return;

        $request_id = $notification->toArray()['params']['requestId'] ?? null;

        // 2025-11-25 lets requestId be left out, for tasks, which tasks/cancel stops instead.
        if(is_string($request_id) || is_int($request_id)) $this->cancel(new RequestId($request_id));
    }

    public function receiveResponse(Result|MCPError $response): void
    {

    }
}
