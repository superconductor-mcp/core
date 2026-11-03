<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Concerns\PromptReference;
use Fabricate\Schema\V20260728\Concerns\ResourceTemplateReference;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\RequestMetaObject;

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
        RequestMetaObject $_meta,
        ?array $context_arguments = null,
    ) {
        $this->argument = ['name' => $argument_name, 'value' => $argument_value];
        $this->context = isset($context_arguments) ? ['arguments' => $context_arguments ?: (object) []] : null;

        parent::__construct($_meta);
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
