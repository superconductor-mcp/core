<?php

namespace Fabricate\Schema\V20260728\Messaging\Elicitation;

use Fabricate\Contracts\Schema\ElicitAction;
use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitResult as ElicitContract;
use Fabricate\Schema\V20260728\Concerns\InputResponse;

final readonly class ElicitResult implements ElicitContract, InputResponse
{
    /**
     * @param ElicitAction $action
     * @param array<string,string|int|float|bool|string[]>|null $content
     */
    public function __construct(
        public ElicitAction $action,
        public ?array $content = null,
    ) {}

    public function toArray(): array
    {
        $results = ['action' => $this->action->value];

        if(isset($this->content)) $results['content'] = $this->content ?: (object) [];

        return $results;
    }
}
