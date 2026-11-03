<?php

namespace Fabricate\Schema\V20260728\Messaging\Autocomplete;

use Fabricate\Contracts\Schema\Messaging\Autocomplete\CompleteResult as CompleteContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20260728\Concerns\ResultTypeEnum;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Schema\V20260728\Messaging\Result;

final readonly class CompleteResult extends Result implements CompleteContract, ServerResult
{
    public array $completion;

    /**
     * @param string[] $values
     * @param int|null $total
     * @param bool|null $hasMore
     * @param string|ResultTypeEnum $resultType
     * @param ResultMetaObject|null $_meta
     */
    public function __construct(
        array $values,
        ?int $total = null,
        ?bool $hasMore = null,
        string|ResultTypeEnum $resultType = ResultTypeEnum::COMPLETE,
        ?ResultMetaObject $_meta = null,
    ) {
        $payload = ['values' => $values];
        if(isset($total)) $payload['total'] = $total;
        if(isset($hasMore)) $payload['hasMore'] = $hasMore;

        $this->completion = $payload;

        parent::__construct($resultType, $_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['completion'] = $this->completion;

        return $results;
    }
}
