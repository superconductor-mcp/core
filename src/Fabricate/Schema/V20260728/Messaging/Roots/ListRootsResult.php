<?php

namespace Fabricate\Schema\V20260728\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsResult as ListRootsContract;
use Fabricate\Schema\V20260728\Capabilities\Roots\Root;
use Fabricate\Schema\V20260728\Concerns\InputResponse;

final readonly class ListRootsResult implements ListRootsContract, InputResponse
{
    /**
     * @param Root[] $roots
     */
    public function __construct(
        public array $roots,
    ) {}

    public function toArray(): array
    {
        return ['roots' => array_map(fn(Root $root) => $root->toArray(), $this->roots)];
    }
}
