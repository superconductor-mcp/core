<?php

namespace App\Mcp\Tools;

use App\Mcp\Resources\PonderingsResource;
use Fabricate\Contracts\Mechanism\Attributes\Argument;
use Fabricate\Contracts\Mechanism\Attributes\ToolAnnotations;
use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Tool;
use Throwable;

/**
 * Blocking work on purpose: it sleeps, then answers. Async, it runs on the IO pool (a thread on ZTS PHP, a worker
 * process otherwise), so the server keeps answering other requests; the answer arrives by mail when the pool is done.
 */
#[ToolCall('ponder', 'Think a message over for some seconds, then send it back reversed.', 'Ponder')]
#[ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false)]
class PonderTool extends Tool
{
    protected static string $task_support = self::TASKS_OPTIONAL;

    protected bool $async = true;

    public function __construct(
        #[Argument('What to think over.')] public readonly string $message,
        #[Argument('How long to think, in seconds.')] public readonly int $seconds = 5,
    ) {}

    public function handle(?RequestContext $context = null): ?string
    {
        $context?->log(LoggingLevel::INFO, "Pondering [{$this->message}] for {$this->seconds}s.", 'ponder');
        $context?->progress(0, $this->seconds, 'Pondering');

        if(! $this->isAsync()) return $this->done(self::ponder($this->message, $this->seconds), $context);

        [$message, $seconds] = [$this->message, $this->seconds];

        app('concurrency')->driver('pool')
            ->async(static fn() => PonderTool::ponder($message, $seconds), 'ponder')
            ->then(function(array $results) use ($context) {
                // A cancelled call keeps nothing: the client was told nothing will come.
                if($context?->cancelled()) return;

                $this->finish($this->done($results[0], $context));
            }, fn(Throwable $reason) => $this->fail($reason));

        return null;
    }

    private function done(string $pondering, ?RequestContext $context): string
    {
        $context?->progress($this->seconds, $this->seconds, 'Pondered');
        $context?->log(LoggingLevel::DEBUG, "Pondered [{$this->message}] into [{$pondering}].", 'ponder');

        return self::recorded($pondering);
    }

    /**
     * Back on the loop: keeps the answer in echo://ponderings, which tells its subscribers.
     */
    private static function recorded(string $pondering): string
    {
        PonderingsResource::record($pondering);

        return $pondering;
    }

    /**
     * The blocking part: runs wherever the pool puts it.
     */
    public static function ponder(string $message, int $seconds): string
    {
        sleep(max(0, $seconds));

        return strrev($message);
    }
}
