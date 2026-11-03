<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\IncludeContext;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\Capabilities\Sampling\ModelPreferences;
use Fabricate\Schema\V20251125\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20251125\Capabilities\Sampling\ToolChoice;
use Fabricate\Schema\V20251125\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20251125\TaskMetadata;

final readonly class CreateMessageRequestParams extends TaskAugmentedRequestParams
{
    /**
     * @param SamplingMessage[] $messages
     * @param string[]|null $stopSequences
     * @param ToolDefinition[]|null $tools
     */
    public function __construct(
        public array $messages,
        public int $maxTokens,
        public ?ModelPreferences $modelPreferences = null,
        public ?string $systemPrompt = null,
        public ?IncludeContext $includeContext = null,
        public int|float|null $temperature = null,
        public ?array $stopSequences = null,
        public ?array $metadata = null,
        public ?array $tools = null,
        public ?ToolChoice $toolChoice = null,
        ?TaskMetadata $task = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($task, $_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['messages'] = array_map(fn(SamplingMessage $message) => $message->toArray(), $this->messages);
        $results['maxTokens'] = $this->maxTokens;
        if(isset($this->modelPreferences)) $results['modelPreferences'] = $this->modelPreferences->toArray() ?: (object) [];
        if(isset($this->systemPrompt)) $results['systemPrompt'] = $this->systemPrompt;
        if(isset($this->includeContext)) $results['includeContext'] = $this->includeContext->value;
        if(isset($this->temperature)) $results['temperature'] = $this->temperature;
        if(isset($this->stopSequences)) $results['stopSequences'] = $this->stopSequences;
        if(isset($this->metadata)) $results['metadata'] = $this->metadata ?: (object) [];
        if(isset($this->tools)) $results['tools'] = array_map(fn(ToolDefinition $tool) => $tool->toArray(), $this->tools);
        if(isset($this->toolChoice)) $results['toolChoice'] = $this->toolChoice->toArray() ?: (object) [];

        return $results;
    }
}
