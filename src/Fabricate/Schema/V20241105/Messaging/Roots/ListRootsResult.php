<?php

namespace Fabricate\Schema\V20241105\Messaging\Roots;

use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\Roots\ListRootsResult as ListRootsContract;
use Fabricate\Schema\V20241105\Capabilities\Roots\Root;
use Fabricate\Schema\V20241105\Messaging\Result;

final readonly class ListRootsResult extends Result implements ListRootsContract, ClientResult
{
    /**
     * @param Root[] $roots
     * @param array|null $_meta
     */
    public function __construct(
        public array $roots,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['roots'] = array_map(fn(Root $root) => $root->toArray(), $this->roots);

        return $results;
    }
}
