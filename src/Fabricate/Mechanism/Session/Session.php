<?php

namespace Fabricate\Mechanism\Session;

use Closure;
use Throwable;
use Voyager\Contracts\IOPools\Loop;
use Fabricate\Mechanism\ProcedureRouter;
use Fabricate\NutsAndBolts\RequestMeta;
use Fabricate\NutsAndBolts\ProgressReporter;
use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\MCPResponse;
use Fabricate\NutsAndBolts\Messages\MCPNotification;
use Fabricate\NutsAndBolts\Messages\Errors\JsonRpcError;
use Fabricate\NutsAndBolts\Messages\Errors\InternalError;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidRequest;
use Fabricate\Mechanism\Workflows\LogIncomingMessage;
use Fabricate\Mechanism\Session\Lifecycle\IdleState;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;

/**
 * The server side of one session, with no transport in it: a message comes in with a closure that sends lines
 * back out, and the session gates it on the lifecycle, runs the procedure, answers, and moves the state.
 */
final class Session
{
    /** @var list<Closure(SessionBag): void> told after every lifecycle change; a transport hangs its timers here */
    private array $on_state_change = [];

    public function __construct(
        public readonly SessionBag $bag,
        private readonly Loop $event_loop,
        private readonly ProcedureRouter $router = new ProcedureRouter(),
    ) {}

    public function onStateChange(Closure $listener): void
    {
        $this->on_state_change[] = $listener;
    }

    /**
     * @param Closure(string): void $send writes one JSON line to the client this message came from
     * @return bool false when the message was nothing the session acts on
     */
    public function handle(mixed $message, Closure $send): bool
    {
        if ($message instanceof MCPRequest) {
            $this->request($message, $send);
        } elseif ($message instanceof MCPNotification) {
            $this->notification($message);
        } elseif ($message instanceof JsonRpcError) {
            // the decoder already built the right error with the right id; a notification never gets one back
            if ($message->replies()) {
                $send($message->toJson());
            }
        } else {
            return false;
        }

        return true;
    }

    // sends what the server queued, filtered by what this session's client wants; nothing reaches a client before the handshake
    public function flushOutbox(Closure $send): void
    {
        $notifications = $this->bag->server_configuration?->drainNotifications() ?? [];

        if ($this->bag->state instanceof IdleState) {
            return;
        }

        foreach ($notifications as $notification) {
            if ($this->bag->wants($notification)) {
                $send($notification->toJson());
            }
        }
    }

    private function request(MCPRequest $request, Closure $send): void
    {
        $bag = $this->bag;

        if (! $bag->their_ids->receive($request->id)) {
            // the client already used this id: refuse it without touching state or routing
            $send((new InvalidRequest($request->id, "Request id [{$request->id}] has already been used."))->toJson());

            return;
        }

        new LogIncomingMessage($request)->dispatch($bag);

        if (! $bag->state->canProcess($request)) {
            $send((new InvalidRequest($request->id, "Method [{$request->method}] is not allowed."))->toJson());

            return;
        }

        $bag->in_flight[$request->id] = true;

        // whatever handles this request can report progress through the container's ProgressReporter;
        // a fiber runs its first stretch at once, so the reporter is registered before the handler is built
        $run = function () use ($request, $bag, $send): MCPResponse {
            $progress = $this->progressReporter($request, $send);
            app()->registerInstance(ProgressReporter::class, $progress);

            try {
                return $this->router->dispatch($request, $bag);
            } finally {
                $progress->finish();
                app()->registerInstance(ProgressReporter::class, ProgressReporter::silent());
            }
        };

        if ($this->router->runsAsync($request)) {
            // the procedure yields whenever it waits on the loop; the transport keeps reading in the meantime
            $this->event_loop->async($run)->then(
                fn (MCPResponse $response) => $this->respond($request, $response, $send),
                fn (Throwable $e) => $this->respond($request, new InternalError($request->id, $e->getMessage()), $send),
            );
        } else {
            $this->respond($request, $run(), $send);
        }
    }

    // the request finished: its response goes out unless the client cancelled it meanwhile
    private function respond(MCPRequest $request, MCPResponse $response, Closure $send): void
    {
        $bag = $this->bag;
        unset($bag->in_flight[$request->id]);

        if (isset($bag->cancelled[$request->id])) {
            unset($bag->cancelled[$request->id]);

            return;
        }

        $send($response->toJson());

        if ($response instanceof InitializeResponse) {
            if ($bag->state instanceof IdleState) {
                $bag->state = $bag->state->nextState();
            }

            $this->stateChanged();
        }

        // anything the server queued while handling this request goes out after its response
        $this->flushOutbox($send);
    }

    private function notification(MCPNotification $notification): void
    {
        $bag = $this->bag;
        new LogIncomingMessage($notification)->dispatch($bag);

        if (! isset($bag->notifications[$notification->method])) {
            return;
        }

        $state_before = $bag->state;
        (new ($bag->notifications[$notification->method])())->handle($bag, $notification);

        // an action that moved the lifecycle (initialized → Available) goes through the same hook as any other change
        if ($bag->state !== $state_before) {
            $this->stateChanged();
        }
    }

    private function progressReporter(MCPRequest $request, Closure $send): ProgressReporter
    {
        $token = RequestMeta::progressToken($request);

        return is_null($token)
            ? ProgressReporter::silent()
            : new ProgressReporter($token, fn ($notification) => $send($notification->toJson()), (float) config('mcp.progress.min_interval', 0.1));
    }

    private function stateChanged(): void
    {
        foreach ($this->on_state_change as $listener) {
            $listener($this->bag);
        }
    }
}
