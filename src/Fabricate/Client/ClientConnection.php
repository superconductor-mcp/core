<?php

namespace Fabricate\Client;

use Closure;
use Fabricate\Client\Signals\ConnectionFailed;
use Fabricate\Contracts\Client\ClientConnection as ClientConnectionContract;
use Fabricate\Contracts\Mechanism\DecodedMessage;
use Fabricate\Contracts\Schema\Messaging\RPC\JSONRPCMessage;
use Fabricate\Contracts\Schema\ProtocolVersion;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\Decoding\DecodedBatch;
use Fabricate\Mechanism\Decoding\InvalidMessage;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Voyager\Contracts\IOPools\Loop;

/**
 * What every transport's client connection does the same way once a session exists. The handshake is not here: it
 * belongs to the versions that have one and to the transport that runs it, so each transport package opens its
 * session its own way and reports the agreed version with started(). Until then nothing is sent on the transport's
 * behalf, and what the server sends waits in the inbox, where the transport's handshake finds its own answers; the
 * rest reaches the client first thing.
 *
 * Once the client exists, every message the transport reads goes to it, and what it queues goes back out. A
 * connection that breaks answers every request in flight with the reason, fires ConnectionFailed, and has no client;
 * failure() says why. Async mail the client's work posts to the loop reaches the session through deliver(), from
 * whoever handles the loop's mail.
 *
 * A transport sends through send(), hands what it reads to received(), reports a break with lost(), and tears itself
 * down in disconnect().
 */
abstract class ClientConnection implements ClientConnectionContract
{
    protected ?MCPClient $client = null;

    protected ?string $failure = null;

    protected bool $closing = false;

    /** The protocol version the session is in, once the transport has agreed on one. */
    public protected(set) ?ProtocolVersion $version = null;

    /** @var list<DecodedMessage|DecodedBatch> What the server sent before the client existed. */
    protected array $inbox = [];

    public function __construct(
        public readonly ClientConfiguration $configuration,
    ) {}

    public function client(): ?MCPClient
    {
        return $this->client;
    }

    public function failure(): ?string
    {
        return $this->failure;
    }

    /**
     * Async mail for this connection's session: an answer or notification the client's work finished with.
     */
    public function deliver(OutboundMessage $mail): void
    {
        if($mail->session_id !== $this->client?->session_id) return;

        $this->client->queue($mail->message());
        $this->sendQueued();
    }

    /**
     * Ends the connection: a request still in flight is answered with an error saying so.
     */
    public function close(): void
    {
        $this->closing = true;
        $this->client?->close();
        $this->client = null;

        $this->disconnect();
    }

    /**
     * Sends one message to the server in the given protocol version.
     */
    abstract protected function send(JSONRPCMessage $message, ProtocolVersion $version): void;

    /**
     * Lets go of the transport: its process, streams or requests.
     */
    abstract protected function disconnect(): void;

    /**
     * The transport agreed on a version: the session starts, with the client built in it, and what the server sent
     * meanwhile reaches it.
     */
    protected function started(ProtocolVersion $version): void
    {
        $this->version = $version;
        $this->client = new MCPClient($this->configuration, $version->value);

        foreach(array_splice($this->inbox, 0) as $message) $this->receive($message);
    }

    /**
     * Runs the loop until $done, or $seconds.
     */
    protected function wait(Closure $done, float $seconds): void
    {
        $loop = app(Loop::class);
        $expired = false;

        // a quiet loop never re-checks the condition, so the timer is what wakes it
        $deadline = $loop->at($seconds, function() use (&$expired) {
            $expired = true;
        });

        try {
            $loop->until(function() use (&$expired, $done) { return $expired || $done(); });
        } finally {
            $deadline->cancel();
        }
    }

    /**
     * One message the transport read: the client's once it exists, otherwise kept until it does.
     */
    protected function received(DecodedMessage|DecodedBatch $message): void
    {
        $this->client ? $this->receive($message) : $this->inbox[] = $message;
    }

    /**
     * One message from the server, once the client exists: it goes to the client, and an answer goes back. On
     * 2026-07-28 a client sends no responses, so a message that could not be read gets none.
     */
    private function receive(DecodedMessage|DecodedBatch $message): void
    {
        if($message instanceof InvalidMessage)
        {
            if($this->version !== ProtocolVersion::V20260728) $this->send($this->refusal($message), $this->version);
        }
        elseif($message instanceof DecodedBatch)
        {
            $this->receiveBatch($message);
        }
        elseif($reply = $this->client->receiveMessage($message))
        {
            $this->client->queue($reply);
        }

        $this->sendQueued();
    }

    /**
     * A batch, which only 2025-03-26 has: the answers to its requests go back as one batch. Other versions refuse it.
     */
    private function receiveBatch(DecodedBatch $batch): void
    {
        if($this->version !== ProtocolVersion::V20250326)
        {
            if($this->version !== ProtocolVersion::V20260728) $this->send($this->refusal(new InvalidMessage(null, RPCErrorCode::INVALID_REQUEST, "Batches are not part of {$this->version->value}.")), $this->version);
            return;
        }

        $answers = [];

        foreach($batch->messages as $message)
        {
            $answer = $message instanceof InvalidMessage
                ? $this->refusal($message)
                : $this->client->receiveMessage($message);

            if($answer) $answers[] = $answer;
        }

        if($answers === []) return;

        $this->sendQueued();
        $this->send(new V20250326\Messaging\RPC\JSONRPCBatchResponse($answers), $this->version);
    }

    /**
     * The session version's JSON-RPC error answering a message that could not be read, under its id when it had one.
     */
    private function refusal(InvalidMessage $message): JSONRPCMessage
    {
        [$id, $code, $reason] = [$message->id(), $message->code->value, $message->message];

        return match($this->version) {
            ProtocolVersion::V20241105 => new V20241105\Messaging\RPC\JSONRPCError('2.0', $id, $code, $reason),
            ProtocolVersion::V20250326 => new V20250326\Messaging\RPC\JSONRPCError('2.0', $id, $code, $reason),
            ProtocolVersion::V20250618 => new V20250618\Messaging\RPC\JSONRPCError('2.0', $id, $code, $reason),
            ProtocolVersion::V20251125 => new V20251125\Messaging\RPC\JSONRPCErrorResponse('2.0', $id, new V20251125\Messaging\MCPError($code, $reason)),
        };
    }

    /**
     * Sends everything the client queued, oldest first.
     */
    protected function sendQueued(): void
    {
        foreach($this->client?->pullQueued() ?? [] as $message) $this->send($message, $this->version);
    }

    /**
     * The connection broke: the client is gone, every request it had in flight gets the reason as its error, and the
     * application hears ConnectionFailed. failure() keeps the first reason given.
     */
    protected function lost(string $reason): void
    {
        $this->failure ??= $reason;

        if(is_null($client = $this->client)) return;

        $this->client = null;
        $client->close($this->failure);

        signal(new ConnectionFailed($client->session_id, $this->failure));
    }
}
