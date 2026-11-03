<?php

namespace Fabricate\Schema\V20250618\Messaging\Autocomplete;

use Fabricate\Contracts\Schema\Messaging\Autocomplete\CompleteRequest as CompleteContract;
use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Concerns\PromptReference;
use Fabricate\Schema\V20250618\Concerns\ResourceTemplateReference;
use Fabricate\Schema\V20250618\Messaging\Request;

final readonly class CompleteRequest extends Request implements CompleteContract, ClientRequest
{
    private PromptReference|ResourceTemplateReference $reference;

    private string $argument_name;

    private string $argument_value;

    private array $context_arguments;

    /**
     * @param array<string,string>|null $context_arguments
     */
    public function __construct(
        PromptReference|ResourceTemplateReference $ref,
        string $argument_name,
        string $argument_value,
        ?array $context_arguments = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null,
    ) {
        $this->reference = $ref;
        $this->argument_name = $argument_name;
        $this->argument_value = $argument_value;
        $this->context_arguments = $context_arguments ?? [];

        $params = [
            'ref' => $ref->toArray(),
            'argument'=> [
                'name' =>  $argument_name,
                'value' => $argument_value
            ]
        ];

        if(isset($context_arguments)) $params['context'] = ['arguments' => $context_arguments ?: (object) []];

        parent::__construct('completion/complete', $params, $_meta, $progress_token);
    }

    public function referenceType(): string
    {
        return $this->reference->type;
    }

    public function referenceName(): string
    {
        return $this->reference instanceof PromptReference ? $this->reference->name : $this->reference->uri;
    }

    public function argumentName(): string
    {
        return $this->argument_name;
    }

    public function argumentValue(): string
    {
        return $this->argument_value;
    }

    public function contextArguments(): array
    {
        return $this->context_arguments;
    }
}
