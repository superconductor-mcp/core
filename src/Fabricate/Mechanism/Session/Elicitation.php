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

    // the client declared this mode, and the session speaks a version that has it (form 2025-06-18, url 2025-11-25)
    public function available(string $mode = 'form'): bool
    {
        if (is_null($this->session)) {
            return false;
        }

        $bag = $this->session->bag;

        return ($bag->protocol_version ?? '') >= '2025-06-18'
            && in_array($mode, Capabilities::elicitationModes($bag->client_capabilities ?? [], $bag->protocol_version), true);
    }

    /**
     * @param ObjectSchema $schema string, number, integer, boolean and enum properties only
     * @throws ElicitationFailed when no answer can be had; a decline or cancel is an answer, not a failure
     */
    public function ask(string $message, ObjectSchema $schema): ElicitAnswer
    {
        if (! $this->available()) {
            throw new ElicitationFailed('elicitation is not available: the client did not declare form mode, the session predates 2025-06-18, or no request is being handled.');
        }

        if (! $schema->isFlat()) {
            throw new ElicitationFailed('elicitation schema: only string, number, integer, boolean and enum properties are allowed.');
        }

        $answer = $this->send(new ElicitRequest($this->session->bag->own_ids->next(), $message, $schema));

        if ($answer->accepted() && ! is_null($problem = $schema->problem($answer->content))) {
            throw new ElicitationFailed("the answer does not match the schema: {$problem}");
        }

        return $answer;
    }

    /**
     * 2025-11-25: send the user to $url for what must not pass through the client (credentials, payment, a
     * third-party sign-in). Accept means the user agreed to open it, not that they finished; the server says
     * when they did with notifications/elicitation/complete for the same $elicitation_id, which the handler
     * would put in the URL to tie the visit to this request.
     *
     * @throws ElicitationFailed when no answer can be had; a decline or cancel is an answer, not a failure
     */
    public function askUrl(string $message, string $url, string $elicitation_id): ElicitAnswer
    {
        if (! $this->available('url')) {
            throw new ElicitationFailed('URL elicitation is not available: the client did not declare url mode, the session predates 2025-11-25, or no request is being handled.');
        }

        try {
            $request = ElicitRequest::url($this->session->bag->own_ids->next(), $message, $url, $elicitation_id);
        } catch (\InvalidArgumentException $e) {
            throw new ElicitationFailed($e->getMessage());
        }

        $answer = $this->send($request);

        // a URL elicitation carries no content back, whatever the client put there
        return $answer->accepted() ? ElicitAnswer::accept([]) : $answer;
    }

    // ask the client and wait for its answer, the timeout, or the asking request's cancellation
    private function send(ElicitRequest $request): ElicitAnswer
    {
        $bag = $this->session->bag;
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
            return ElicitResponse::fromReceived($received)->answer;
        } catch (UnexpectedValueException $e) {
            throw new ElicitationFailed($e->getMessage());
        }
    }
}
