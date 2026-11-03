<?php

namespace Fabricate\Schema\V20241105\Messaging\Sampling;

use Fabricate\Contracts\Schema\IncludeContext;
use Fabricate\Contracts\Schema\Messaging\Sampling\CreateMessageRequest as CreateMessageContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20241105\Capabilities\Sampling\ModelPreferences;
use Fabricate\Schema\V20241105\Capabilities\Sampling\SamplingMessage;
use Fabricate\Schema\V20241105\Messaging\Request;

final readonly class CreateMessageRequest extends Request implements CreateMessageContract, ServerRequest
{
    /**
     * @param SamplingMessage[] $messages
     * @param int $max_tokens
     * @param ModelPreferences|null $model_preferences
     * @param string|null $system_prompt
     * @param IncludeContext|null $include_context
     * @param int|float|null $temperature
     * @param string[]|null $stop_sequences
     * @param array|null $metadata
     * @param ProgressToken|null $progress_token
     */
    public function __construct(
        array $messages,
        int $max_tokens,
        ?ModelPreferences $model_preferences = null,
        ?string $system_prompt = null,
        ?IncludeContext $include_context = null,
        int|float|null $temperature = null,
        ?array $stop_sequences = null,
        ?array $metadata = null,
        ?ProgressToken $progress_token = null,
    ) {
        $params = [
            'messages' => array_map(fn(SamplingMessage $message) => $message->toArray(), $messages),
            'maxTokens' => $max_tokens,
        ];

        if(isset($model_preferences)) $params['modelPreferences'] = $model_preferences->toArray() ?: (object) [];
        if(isset($system_prompt)) $params['systemPrompt'] = $system_prompt;
        if(isset($include_context)) $params['includeContext'] = $include_context->value;
        if(isset($temperature)) $params['temperature'] = $temperature;
        if(isset($stop_sequences)) $params['stopSequences'] = $stop_sequences;
        if(isset($metadata)) $params['metadata'] = $metadata ?: (object) [];

        parent::__construct('sampling/createMessage', $params, $progress_token);
    }
}
