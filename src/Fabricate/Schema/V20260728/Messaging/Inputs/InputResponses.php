<?php

namespace Fabricate\Schema\V20260728\Messaging\Inputs;

use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class InputResponses implements Arrayable
{
    /**
     * @param array<string,InputResponse> $responses
     */
    public function __construct(
        public array $responses = []
    ) {}

    public function toArray(): array
    {
        return array_map(fn(InputResponse $response) => $response->toArray(), $this->responses);
    }
}
