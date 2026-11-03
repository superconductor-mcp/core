<?php

namespace Fabricate\Schema\V20260728\Messaging\Inputs;

use Fabricate\Schema\V20260728\Concerns\InputRequest;
use Voyager\Contracts\NutsAndBolts\Arrayable;

readonly class InputRequests implements Arrayable
{
    /**
     * @param array<string,InputRequest> $requests
     */
    public function __construct(
        public array $requests = []
    ) {}

    public function toArray(): array
    {
        return array_map(fn(InputRequest $request) => $request->toArray(), $this->requests);
    }
}
