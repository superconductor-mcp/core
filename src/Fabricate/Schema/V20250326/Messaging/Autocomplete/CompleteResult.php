<?php

namespace Fabricate\Schema\V20250326\Messaging\Autocomplete;

use Fabricate\Contracts\Schema\Messaging\Autocomplete\CompleteResult as CompleteContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20250326\Messaging\Result;

final readonly class CompleteResult extends Result implements CompleteContract, ServerResult
{
    public array $completion;

    /**
     * @param string[] $values
     * @param int|null $total
     * @param bool|null $hasMore
     * @param array|null $_meta
     */
    public function __construct(
        array $values,
        ?int $total = null,
        ?bool $hasMore = null,
        ?array $_meta = null,
    ) {
        $payload = ['values' => $values];
        if(isset($total)) $payload['total'] = $total;
        if(isset($hasMore)) $payload['hasMore'] = $hasMore;

        $this->completion = $payload;

        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['completion'] = $this->completion;

        return $results;
    }
}
