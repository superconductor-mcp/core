<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\Concerns\PromptReference;
use Fabricate\Schema\V20251125\Concerns\ResourceTemplateReference;

final readonly class CompleteRequestParams extends RequestParams
{
    public array $argument;
    public ?array $context;

    /**
     * @param array<string,string>|null $context_arguments
     */
    public function __construct(
        public PromptReference|ResourceTemplateReference $ref,
        string $argument_name,
        string $argument_value,
        ?array $context_arguments = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        $this->argument = ['name' => $argument_name, 'value' => $argument_value];
        $this->context = isset($context_arguments) ? ['arguments' => $context_arguments ?: (object) []] : null;

        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['ref'] = $this->ref->toArray();
        $results['argument'] = $this->argument;
        if(isset($this->context)) $results['context'] = $this->context;

        return $results;
    }
}
