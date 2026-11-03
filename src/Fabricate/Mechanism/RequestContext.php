<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Notification;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Mechanism\Mail\OutboundMessage;
use Fabricate\Mechanism\Signals\LogMessage;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Voyager\Contracts\IOPools\Loop;

/**
 * The request a procedure is answering: which session it came in on, its id, the session's protocol version, and the
 * progress token the client sent with it, if any. Async work uses it to post its answer, or a notification, back to
 * the loop as mail. Tools, prompts and resources can ask for it in handle() or read() to log, report progress, and see
 * whether the client cancelled.
 */
final readonly class RequestContext
{
    public function __construct(
        public string $session_id,
        public RequestId $id,
        public string $protocol_version,
        public ?ProgressToken $progress_token = null,
        public Cancellation $cancellation = new Cancellation(),
        /** On 2026-07-28, the lowest level the request wants logged, from _meta; no logging at all when null. */
        public ?LoggingLevel $log_level = null,
        /** On 2026-07-28, the client capabilities the request declared in _meta, as sent. */
        public array $client_capabilities = [],
    ) {}

    /**
     * Whether the client cancelled this request. Work that can stop should; whatever it produces is not sent.
     */
    public function cancelled(): bool
    {
        return $this->cancellation->cancelled;
    }

    /**
     * Logs to the client as notifications/message, filtered by the level it set with logging/setLevel, or on 2026-07-28
     * by the level the request named.
     */
    public function log(LoggingLevel $level, mixed $data, ?string $logger = null): void
    {
        if(! $this->cancelled()) signal(new LogMessage($this->session_id, $this->id, $level, $data, $logger, $this->log_level));
    }

    /**
     * Reports progress as notifications/progress, when the client asked for it with a progress token. The progress
     * must go up with every report; $message is dropped in 2024-11-05, which has none.
     */
    public function progress(int|float $progress, int|float|null $total = null, ?string $message = null): void
    {
        if(is_null($this->progress_token)) return;

        $this->notify(match($this->protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Progress\ProgressNotification($this->progress_token, $progress, $total),
            '2025-03-26' => new V20250326\Messaging\Progress\ProgressNotification($this->progress_token, $progress, $total, $message),
            '2025-06-18' => new V20250618\Messaging\Progress\ProgressNotification($this->progress_token, $progress, $total, $message),
            '2025-11-25' => new V20251125\Messaging\Progress\ProgressNotification(new V20251125\Messaging\Params\ProgressNotificationParams($this->progress_token, $progress, $total, $message)),
            '2026-07-28' => new V20260728\Messaging\Progress\ProgressNotification(new V20260728\Messaging\Params\ProgressNotificationParams($this->progress_token, $progress, $total, $message)),
        });
    }

    /**
     * Posts the request's answer to the loop. The transport running the session queues it on its next turn.
     */
    public function reply(Result|MCPError $answer): void
    {
        if($this->cancelled()) return;

        $this->cancellation->answer();
        app(Loop::class)->post(new OutboundMessage($this->session_id, $this->protocol_version, $answer, $this->id));
    }

    /**
     * Posts a notification for this request's session to the loop, marked as sent while answering this request.
     */
    public function notify(Notification $notification): void
    {
        if($this->cancelled()) return;

        app(Loop::class)->post(new OutboundMessage($this->session_id, $this->protocol_version, $notification, related_request: $this->id));
    }
}
