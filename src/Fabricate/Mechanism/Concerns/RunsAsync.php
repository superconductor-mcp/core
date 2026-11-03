<?php

namespace Fabricate\Mechanism\Concerns;

use Fabricate\Mechanism\Completion;
use Throwable;

/**
 * Lets a tool, prompt or resource opt into running async by setting $async. When the procedure can wait for it,
 * it hands over a Completion, and the concrete starts its work, returns, and later calls finish() or fail().
 * When it can't (no session to post the answer to), isAsync() is false and the concrete runs its sync path.
 */
trait RunsAsync
{
    /** Set to true in a concrete whose work is worth running off the loop's turn. */
    protected bool $async = false;

    private ?Completion $completion = null;

    /**
     * Called by the procedure before handle() or read() when this concrete may go async.
     */
    public function deferTo(Completion $completion): void
    {
        $this->completion = $completion;
    }

    public function wantsAsync(): bool
    {
        return $this->async;
    }

    /** Whether this call runs async: the concrete asked to, and the procedure gave it somewhere to deliver. */
    protected function isAsync(): bool
    {
        return $this->async && isset($this->completion);
    }

    /** Hands back what the async work produced; the procedure turns it into the result. */
    protected function finish(mixed $output): void
    {
        $this->completion?->finish($output);
    }

    /** Hands back why the async work failed. */
    protected function fail(Throwable $reason): void
    {
        $this->completion?->fail($reason);
    }
}
