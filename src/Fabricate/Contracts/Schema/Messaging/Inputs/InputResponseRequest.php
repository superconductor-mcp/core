<?php

namespace Fabricate\Contracts\Schema\Messaging\Inputs;

use Fabricate\Schema\V20260728\Messaging\Inputs\InputResponses;

/**
 * A request that can be the retry of one answered with InputRequiredResult: tools/call, prompts/get and resources/read
 * from 2026-07-28. It carries the client's inputResponses and echoes the server's requestState.
 */
interface InputResponseRequest
{
    public function inputResponses(): ?InputResponses;

    public function requestState(): ?string;
}
