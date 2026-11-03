<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\ProgressToken;

final readonly class SetLevelRequestParams extends RequestParams
{
    public function __construct(
        public LoggingLevel $level,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['level'] = $this->level->value;

        return $results;
    }
}
