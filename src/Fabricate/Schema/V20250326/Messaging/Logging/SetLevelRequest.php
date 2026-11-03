<?php

namespace Fabricate\Schema\V20250326\Messaging\Logging;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Logging\SetLevelRequest as SetLevelContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250326\Messaging\Request;

final readonly class SetLevelRequest extends Request implements SetLevelContract, ClientRequest
{
    private LoggingLevel $level;

    public function __construct(
        LoggingLevel $level,
        ?ProgressToken $progress_token = null,
    ) {
        $this->level = $level;

        parent::__construct("logging/setLevel", ['level' => $level->value], $progress_token);
    }

    public function level(): LoggingLevel
    {
        return $this->level;
    }
}
