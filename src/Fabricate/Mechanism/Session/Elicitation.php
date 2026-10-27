<?php

namespace Fabricate\Mechanism\Session;

use Closure;
use UnexpectedValueException;
use Voyager\Contracts\IOPools\Loop;
use Fabricate\NutsAndBolts\Capabilities;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAnswer;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitRequest;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitResponse;
use Fabricate\NutsAndBolts\Messages\Cancellation\CancelledNotification;

/**
 * 2025-06-18: a tool, resource or prompt handler asks the user something through the client and waits for the answer.
 * Built per request, like ProgressReporter: inject it in the handler's constructor. ask() waits in the request's fiber,
 * so the loop keeps turning while the user types.
 */
final class Elicitation
{
    public function __construct(
        private readonly ?Session $session = null,
        private readonly ?Closure $send = null,
        private readonly string|int|null $origin = null,
        private readonly ?Loop $event_loop = null,
    ) {}

    // the instance the container holds between requests: nobody to ask
    public static function unavailable(): self
    {
        return new self();
    }

    // the client said it can show a form, and the session speaks a version that has elicitation
    public function available(): bool
    {
        if (is_null($this->session)) {
            return false;
        }

        $bag = $this->session->bag;

        return ($bag->protocol_version ?? '') >= '2025-06-18'
            && Capabilities::declares($bag->client_capabilities ?? [], ['elicitation']);
    }

    /**
     * @param ObjectSchema $schema string, number, integer, boolean and enum properties only
     * @throws ElicitationFailed when no answer can be had; a decline or cancel is an answer, not a failure
     */
    public function ask(string $message, ObjectSchema $schema): ElicitAnswer
    {
        if (! $this->available()) {
            throw new ElicitationFailed('elicitation is not available: the client did not declare it, the session predates 2025-06-18, or no request is being handled.');
        }

        if (! $schema->isFlat()) {
            throw new ElicitationFailed('elicitation schema: only string, number, integer, boolean and enum properties are allowed.');
        }

        $bag = $this->session->bag;
        $request = new ElicitRequest($bag->own_ids->next(), $message, $schema);
        $key = Session::key($request->id);
        $expired = false;
        $timeout = (float) config('mcp.elicitation.timeout', 600);
        $timer = $this->event_loop->at($timeout, function () use (&$expired): void {
            $expired = true;
        });

        $this->session->ask($request, $this->send);

        $origin = $this->origin;
        $this->event_loop->until(function () use ($bag, $key, $origin, &$expired): bool {
            return isset($bag->answers[$key]) || isset($bag->cancelled[$origin]) || $expired;
        });
        $timer->cancel();

        if (! isset($bag->answers[$key])) {
            unset($bag->awaiting[$key]);
            $why = $expired ? "no answer within {$timeout}s." : 'the request that asked was cancelled.';
            ($this->send)((new CancelledNotification($request->id, $why))->toJson());

            throw new ElicitationFailed("elicitation abandoned: {$why}");
        }

        $received = $bag->answers[$key];
        unset($bag->answers[$key]);

        if (! is_null($received->error)) {
            throw new ElicitationFailed('the client refused the elicitation: '.($received->error['message'] ?? 'no reason given').'.');
        }

        try {
            $answer = ElicitResponse::fromReceived($received)->answer;
        } catch (UnexpectedValueException $e) {
            throw new ElicitationFailed($e->getMessage());
        }

        if ($answer->accepted() && ! is_null($problem = $schema->problem($answer->content))) {
            throw new ElicitationFailed("the answer does not match the schema: {$problem}");
        }

        return $answer;
    }
}
