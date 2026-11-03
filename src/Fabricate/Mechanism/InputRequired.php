<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Schema\V20260728\Messaging\Elicitation\ElicitRequest;
use Fabricate\Schema\V20260728\Messaging\Roots\ListRootsRequest;
use Fabricate\Schema\V20260728\Messaging\Sampling\CreateMessageRequest;

/**
 * What a tool, prompt or resource returns, from 2026-07-28, when it needs the client's input before it can answer: the
 * input requests by key, and state to get back on the retry. The procedure answers with InputRequiredResult, sealing
 * the state so the client can't alter it; the retry hands the capability $input_responses and $request_state.
 */
final readonly class InputRequired
{
    /**
     * @param array<string, ElicitRequest|CreateMessageRequest|ListRootsRequest> $requests Each keyed by an identifier unique in this request.
     * @param mixed $state Anything json_encode() can write, handed back as $request_state on the retry.
     */
    public function __construct(
        public array $requests = [],
        public mixed $state = null,
    ) {
        if($requests === [] && is_null($state)) {
            throw new MCPMechanismException('An InputRequired needs input requests, state, or both.');
        }

        foreach($requests as $key => $request) {
            if(! is_string($key) || ! ($request instanceof ElicitRequest || $request instanceof CreateMessageRequest || $request instanceof ListRootsRequest)) {
                throw new MCPMechanismException('InputRequired requests are elicitation, sampling or roots requests, each under a string key.');
            }
        }
    }
}
