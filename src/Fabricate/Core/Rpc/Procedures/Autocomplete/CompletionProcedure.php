<?php

namespace Fabricate\Core\Rpc\Procedures\Autocomplete;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Schema\Messaging\Autocomplete\CompleteRequest;
use Fabricate\Contracts\Schema\Messaging\Autocomplete\CompleteResult;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Mechanism\CompletionResult;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Fabricate\Server\PromptRegistry;
use Fabricate\Server\ResourceTemplateRegistry;

#[CompatibleVersions('2024-11-05', '2025-03-26', '2025-06-18', '2025-11-25', '2026-07-28')]
#[RequiresCapability('completions')]
class CompletionProcedure extends Procedure
{
    public function __construct(
        protected PromptRegistry $prompts,
        protected ResourceTemplateRegistry $templates,
    ) {}

    /**
     * Suggestions for a prompt argument from the prompt's complete(), or for a resource template variable from the template's.
     */
    public function handle(CompleteRequest $request, ServerConfiguration $entity_configuration, string $protocol_version): CompleteResult
    {
        $completion = $request->referenceType() === 'ref/prompt'
            ? $this->promptCompletion($request, $entity_configuration)
            : $this->templateCompletion($request, $entity_configuration);

        $has_more = $completion->has_more ?: null;

        return match($protocol_version) {
            '2024-11-05' => new V20241105\Messaging\Autocomplete\CompleteResult($completion->values, $completion->total, $has_more),
            '2025-03-26' => new V20250326\Messaging\Autocomplete\CompleteResult($completion->values, $completion->total, $has_more),
            '2025-06-18' => new V20250618\Messaging\Autocomplete\CompleteResult($completion->values, $completion->total, $has_more),
            '2025-11-25' => new V20251125\Messaging\Autocomplete\CompleteResult($completion->values, $completion->total, $has_more),
            '2026-07-28' => new V20260728\Messaging\Autocomplete\CompleteResult($completion->values, $completion->total, $has_more, _meta: $this->resultMeta($entity_configuration, $protocol_version)),
        };
    }

    private function templateCompletion(CompleteRequest $request, ServerConfiguration $entity_configuration): CompletionResult
    {
        $uri_template = $request->referenceName();

        if(! in_array($uri_template, $entity_configuration->resources(), true) || ! $this->templates->has($uri_template)) {
            throw new SuperconductorMCPException("Unknown resource template: [{$uri_template}].", RPCErrorCode::INVALID_PARAMS->value);
        }

        $template = $this->templates->template($uri_template);
        $argument = $request->argumentName();

        if(! in_array($argument, $template::variables(), true)) {
            throw new SuperconductorMCPException("Resource template [{$uri_template}] has no variable [{$argument}].", RPCErrorCode::INVALID_PARAMS->value);
        }

        return $template::complete($argument, $request->argumentValue(), $request->contextArguments());
    }

    private function promptCompletion(CompleteRequest $request, ServerConfiguration $entity_configuration): CompletionResult
    {
        $name = $request->referenceName();

        if(! in_array($name, $entity_configuration->prompts(), true)) {
            throw new SuperconductorMCPException("Unknown prompt: [{$name}].", RPCErrorCode::INVALID_PARAMS->value);
        }

        $prompt = $this->prompts->prompt($name);
        $argument = $request->argumentName();

        if(! in_array($argument, array_column($prompt::arguments(), 'name'), true)) {
            throw new SuperconductorMCPException("Prompt [{$name}] has no argument [{$argument}].", RPCErrorCode::INVALID_PARAMS->value);
        }

        return $prompt::complete($argument, $request->argumentValue(), $request->contextArguments());
    }
}
