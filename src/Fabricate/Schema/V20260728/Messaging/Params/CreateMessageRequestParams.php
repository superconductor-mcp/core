<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Contracts\Schema\IncludeContext;
use Fabricate\Schema\V20260728\Capabilities\Sampling\ModelPreferences;
use Fabricate\Schema\V20260728\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20260728\Capabilities\Sampling\ToolChoice;
use Fabricate\Schema\V20260728\Capabilities\Tools\ToolDefinition;
use Fabricate\Schema\V20260728\JSONObject;
use Voyager\Contracts\NutsAndBolts\Arrayable;

final readonly class CreateMessageRequestParams implements Arrayable
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
        public ?JSONObject $metadata = null,
        public ?array $tools = null,
        public ?ToolChoice $toolChoice = null,
    ) {}

    public function toArray(): array
    {
        $results = [
            'messages' => array_map(fn(SamplingMessage $message) => $message->toArray(), $this->messages),
            'maxTokens' => $this->maxTokens,
        ];

        if(isset($this->modelPreferences)) $results['modelPreferences'] = $this->modelPreferences->toArray() ?: (object) [];
        if(isset($this->systemPrompt)) $results['systemPrompt'] = $this->systemPrompt;
        if(isset($this->includeContext)) $results['includeContext'] = $this->includeContext->value;
        if(isset($this->temperature)) $results['temperature'] = $this->temperature;
        if(isset($this->stopSequences)) $results['stopSequences'] = $this->stopSequences;
        if(isset($this->metadata)) $results['metadata'] = $this->metadata->toArray() ?: (object) [];
        if(isset($this->tools)) $results['tools'] = array_map(fn(ToolDefinition $tool) => $tool->toArray(), $this->tools);
        if(isset($this->toolChoice)) $results['toolChoice'] = $this->toolChoice->toArray() ?: (object) [];

        return $results;
    }
}
