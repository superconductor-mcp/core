<?php

namespace Fabricate\Mechanism;

/**
 * Whether the client cancelled a request with notifications/cancelled while its answer was still coming. Once
 * cancelled, nothing more is sent for the request: not its answer, and no notifications. Also whether a log message or
 * progress notification went out for the request, as loop mail; its answer then goes out as mail too, so it can't
 * overtake them.
 */
final class Cancellation
{
    public private(set) bool $cancelled = false;

    /** Set once the answer has gone out; a request answered can't be cancelled. */
    public private(set) bool $answered = false;

    /** Set once a log message or progress notification was posted for the request. */
    public private(set) bool $notified = false;

    public function cancel(): void
    {
        if(! $this->answered) $this->cancelled = true;
    }

    public function answer(): void
    {
        $this->answered = true;
    }

    public function notify(): void
    {
        $this->notified = true;
    }
}
