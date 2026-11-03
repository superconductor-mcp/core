<?php

namespace Fabricate\Core\Rpc\Procedures;

use Error;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\Icon as IconAttribute;
use Fabricate\Contracts\Mechanism\Attributes\ResourceAnnotations;
use Fabricate\Mechanism\Invoker;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Schema\V20260728\Messaging\Inputs\InputRequiredResult;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Schema\V20260728;
use Fabricate\Mechanism\RequestState;
use Fabricate\Mechanism\InputRequired;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Schema\Messaging\Inputs\InputResponseRequest;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\ResultMetaObject;
use Fabricate\Mechanism\RequestContext;

abstract class Procedure
{
    /**
     * Calls a tool's or prompt's handle() through the container. A parameter typed as the server configuration
     * (the contract, the base class or the concrete) gets the configuration, and $protocol_version gets the version.
     */
    protected function callHandle(object $target, ServerConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): mixed
    {
        return $this->callWith($target, 'handle', $entity_configuration, $protocol_version, [], $context);
    }

    /**
     * Calls a method of a tool, prompt or resource through the container, injecting the server configuration and
     * $protocol_version as callHandle() does, plus the request's context and any named values in $named.
     *
     * @param array<string, mixed> $named
     */
    protected function callWith(object $target, string $method, ServerConfiguration $entity_configuration, string $protocol_version, array $named = [], ?RequestContext $context = null): mixed
    {
        return Invoker::call($target, $method, $entity_configuration, $protocol_version, $named, $context);
    }

    /**
     * Why a constructor refused the arguments, without the server file and line PHP names in the message.
     */
    protected function argumentError(Error $e): string
    {
        return Invoker::argumentError($e);
    }

    /**
     * The fully qualified name of a schema class in a protocol version, such as Messaging\MCPError.
     *
     * @return class-string
     */
    protected function schema(string $protocol_version, string $class): string
    {
        return 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\'.$class;
    }

    /**
     * A tool's, prompt's or resource's #[Icon]s as the schema's icons, or null when it has none.
     *
     * @param class-string<\Fabricate\Contracts\Mechanism\Tool|\Fabricate\Contracts\Mechanism\Prompt|\Fabricate\Contracts\Mechanism\Resource|\Fabricate\Contracts\Mechanism\ResourceTemplate> $class
     * @return list<Icon>|null
     */
    protected function icons(string $class): ?array
    {
        return array_map(fn(IconAttribute $icon) => new Icon($icon->src, $icon->mimeType, $icon->sizes, $icon->theme), $class::icons()) ?: null;
    }

    /**
     * A resource's or template's #[ResourceAnnotations] in the requested version, from 2025-03-26, or null when it has
     * none. lastModified goes out from 2025-11-25.
     */
    protected function resourceAnnotations(?ResourceAnnotations $annotations, string $protocol_version): ?object
    {
        if(is_null($annotations) || $protocol_version === '2024-11-05') return null;

        $class = $this->schema($protocol_version, 'Concerns\\Annotations');

        return in_array($protocol_version, ['2025-03-26', '2025-06-18'], true)
            ? new $class($annotations->audience, $annotations->priority)
            : new $class($annotations->audience, $annotations->priority, $annotations->lastModified);
    }

    /**
     * What a 2026-07-28 result carries in _meta: the server's identity, which every modern result should name since no
     * handshake did. Null in the earlier versions, and for a configuration that names no version.
     */
    protected function resultMeta(ServerConfiguration $entity_configuration, string $protocol_version): ?ResultMetaObject
    {
        if($protocol_version !== '2026-07-28' || ! $entity_configuration->identifiesItself()) return null;

        return new ResultMetaObject($entity_configuration->implementation($protocol_version));
    }

    /**
     * What a capability's InputRequired becomes: on 2026-07-28 an InputRequiredResult, with its state sealed to this
     * request. Every input it asks for must be one the client declared, or the request fails with -32021 naming the
     * missing capabilities. Earlier versions have no input_required result.
     *
     * @param string $target The tool's or prompt's name, or the resource's URI.
     */
    protected function inputRequired(InputRequired $input, string $method, string $target, ?RequestContext $context, ServerConfiguration $entity_configuration, string $protocol_version): InputRequiredResult
    {
        if($protocol_version !== '2026-07-28') {
            throw new SuperconductorMCPException("[{$target}] asked for client input, which {$protocol_version} has no input_required result for.", RPCErrorCode::INTERNAL_ERROR->value);
        }

        $missing = [];

        foreach($input->requests as $request) {
            $capability = match(true) {
                $request instanceof V20260728\Messaging\Elicitation\ElicitRequest => 'elicitation',
                $request instanceof V20260728\Messaging\Sampling\CreateMessageRequest => 'sampling',
                $request instanceof V20260728\Messaging\Roots\ListRootsRequest => 'roots',
            };

            if(! isset($context?->client_capabilities[$capability])) $missing[$capability] = (object) [];
        }

        if($missing !== []) {
            throw new SuperconductorMCPException("[{$target}] needs client capabilities the request didn't declare: ".implode(', ', array_keys($missing)).'.', MCPErrorCode::MISSING_REQUIRED_CLIENT_CAPABILITY->value, data: ['requiredCapabilities' => $missing]);
        }

        return new V20260728\Messaging\Inputs\InputRequiredResult(
            V20260728\Concerns\ResultTypeEnum::INPUT_REQUIRED,
            $input->requests === [] ? null : new V20260728\Messaging\Inputs\InputRequests($input->requests),
            is_null($input->state) ? null : RequestState::seal($input->state, $method, $target),
            $this->resultMeta($entity_configuration, $protocol_version),
        );
    }

    /**
     * inputRequired() for an answer that arrives later by mail, where a refusal can't be thrown to the router: it comes
     * back as the version's error instead.
     */
    protected function laterInputRequired(InputRequired $input, string $method, string $target, ?RequestContext $context, ServerConfiguration $entity_configuration, string $protocol_version): InputRequiredResult|MCPError
    {
        try {
            return $this->inputRequired($input, $method, $target, $context, $entity_configuration, $protocol_version);
        } catch(SuperconductorMCPException $e) {
            return new ($this->schema($protocol_version, 'Messaging\\MCPError'))($e->getCode(), $e->getMessage(), $e->data);
        }
    }

    /**
     * What a retry of a request answered with InputRequiredResult hands its capability, by name: the client's
     * $input_responses, and the $request_state this server sealed, once it has passed verification. Nothing for a
     * request that isn't a retry.
     *
     * @return array<string, mixed>
     */
    protected function retryInput(object $request, string $method, string $target): array
    {
        if(! $request instanceof InputResponseRequest) return [];

        $sealed = $request->requestState();

        return [
            'input_responses' => $request->inputResponses(),
            'request_state' => is_null($sealed) ? null : RequestState::open($sealed, $method, $target),
        ];
    }
}
