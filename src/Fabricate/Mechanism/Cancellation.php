<?php

namespace Fabricate\Mechanism;

/**
 * Whether the client cancelled a request with notifications/cancelled while its answer was still coming. Once
 * cancelled, nothing more is sent for the request: not its answer, and no notifications.
 */
final class Cancellation
{
    public private(set) bool $cancelled = false;

    /** Set once the answer has gone out; a request answered can't be cancelled. */
    public private(set) bool $answered = false;

    public function cancel(): void
    {
        if(! $this->answered) $this->cancelled = true;
    }

    public function answer(): void
    {
        $this->answered = true;
    }
}
