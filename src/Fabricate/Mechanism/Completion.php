<?php

namespace Fabricate\Mechanism;

use Closure;
use Throwable;

/**
 * How an async capability hands back its outcome. Whoever started it says how to turn the outcome into what it
 * delivers, and where: a procedure builds a result for the request's answer or a task; a tool that runs another
 * tool relays the outcome into its own. Only the first outcome counts.
 */
final class Completion
{
    private bool $settled = false;

    /**
     * @param Closure(mixed): mixed $build Turns what the capability produced into what to deliver.
     * @param Closure(Throwable): mixed $fail Turns a failure into what to deliver.
     * @param Closure(mixed): void $deliver Sends it on.
     */
    public function __construct(
        private readonly Closure $build,
        private readonly Closure $fail,
        private readonly Closure $deliver,
    ) {}

    public function finish(mixed $output): void
    {
        if($this->settled) return;

        try {
            $result = ($this->build)($output);
        } catch(Throwable $e) {
            $result = ($this->fail)($e);
        }

        $this->settle($result);
    }

    public function fail(Throwable $reason): void
    {
        if($this->settled) return;

        $this->settle(($this->fail)($reason));
    }

    public function settled(): bool
    {
        return $this->settled;
    }

    private function settle(mixed $result): void
    {
        $this->settled = true;

        ($this->deliver)($result);
    }
}
