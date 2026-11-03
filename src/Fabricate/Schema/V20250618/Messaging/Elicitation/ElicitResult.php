<?php

namespace Fabricate\Schema\V20250618\Messaging\Elicitation;

use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitResult as ElicitContract;
use Fabricate\Schema\V20250618\Messaging\Result;

final readonly class ElicitResult extends Result implements ElicitContract, ClientResult
{
    /**
     * @param ElicitAction $action
     * @param array<string,string|int|float|bool>|null $content
     * @param array|null $_meta
     */
    public function __construct(
        public ElicitAction $action,
        public ?array $content = null,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['action'] = $this->action->value;
        if(isset($this->content)) $results['content'] = $this->content ?: (object) [];

        return $results;
    }
}
