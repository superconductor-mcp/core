<?php

namespace Fabricate\NutsAndBolts;

use Closure;
use Fabricate\NutsAndBolts\Messages\Progress\ProgressNotification;

// handed to whatever is handling the current request; sends notifications/progress while the work runs
final class ProgressReporter
{
    private float|int|null $last = null;

    private float $last_sent_at = 0.0;

    private bool $finished = false;

    /**
     * @param Closure(ProgressNotification): void $send writes the notification out right away
     */
    public function __construct(
        private readonly string|int|null $token,
        private readonly Closure $send,
        private readonly float $min_interval = 0.0,
    ) {}

    // a reporter for a request that carried no progressToken: every report is a no-op
    public static function silent(): self
    {
        return new self(null, static fn (): null => null);
    }

    public function report(float|int $progress, float|int|null $total = null): void
    {
        // nothing asked for progress, the request already finished, or the value did not increase (the spec requires it to)
        if (is_null($this->token) || $this->finished || (! is_null($this->last) && $progress <= $this->last)) {
            return;
        }

        $now = microtime(true);
        $reached_total = ! is_null($total) && $progress >= $total;

        // rate limited, except the report that reaches the total, so the client always sees completion
        if (! $reached_total && $now - $this->last_sent_at < $this->min_interval) {
            return;
        }

        $this->last = $progress;
        $this->last_sent_at = $now;
        ($this->send)(new ProgressNotification($this->token, $progress, $total));
    }

    // progress notifications must stop once the request completes
    public function finish(): void
    {
        $this->finished = true;
    }
}
