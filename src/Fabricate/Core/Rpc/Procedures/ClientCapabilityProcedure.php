<?php

namespace Fabricate\Core\Rpc\Procedures;

use Closure;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Client\Elicitation;
use Fabricate\Client\Roots;
use Fabricate\Client\Sampling;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Schema\V20260728\Concerns\InputResponse;
use Fabricate\Contracts\Schema\Messaging\Result;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Mechanism\Completion;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Invoker;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Schema\V20251125;
use Fabricate\Server\Tasks\TaskRecord;
use Throwable;

/**
 * What a client answers a server with, from the concrete the developer wrote: roots/list from its Roots,
 * sampling/createMessage from its Sampling, elicitation/create from its Elicitation. The concrete returns the result in
 * the request's version, or finishes with it later when it runs async. On 2025-11-25 a request asking for a task the
 * client offers runs as one.
 */
abstract class ClientCapabilityProcedure extends Procedure
{
    /** Each method's concrete: the configuration method naming it, the base it extends, and its result class. */
    private const array CAPABILITIES = [
        'roots/list' => ['roots', Roots::class, 'Messaging\\Roots\\ListRootsResult'],
        'sampling/createMessage' => ['sampling', Sampling::class, 'Messaging\\Sampling\\CreateMessageResult'],
        'elicitation/create' => ['elicitation', Elicitation::class, 'Messaging\\Elicitation\\ElicitResult'],
    ];

    /**
     * Answers the request: as a task when it asked for one the client offers, async when the concrete runs async and
     * there is a session to reply on, otherwise with the result now.
     */
    protected function answer(Request $request, ClientConfiguration $configuration, string $protocol_version, ?RequestContext $context): Result|Deferred
    {
        if($none = self::withoutConcrete($request, $configuration, $protocol_version)) return $none;

        $concrete = self::concrete($request, $configuration, $protocol_version);

        if($task = $this->taskFor($request, $configuration, $protocol_version, $context))
        {
            self::run($concrete, $request, $configuration, $protocol_version, $context, function(Result|MCPError $outcome) use ($task, $context) {
                $task->settle($outcome);
                $context?->notify($task->statusNotification());
            }, isset($context));

            return new V20251125\Messaging\Tasks\CreateTaskResult($task->toSchema());
        }

        if($concrete->wantsAsync() && isset($context))
        {
            self::run($concrete, $request, $configuration, $protocol_version, $context, fn(Result|MCPError $outcome) => $context->reply($outcome), true);

            return new Deferred();
        }

        // In line, a refusal the concrete throws reaches the router, which answers with it.
        return self::checked(Invoker::call($concrete, 'handle', $configuration, $protocol_version, [$request::class => $request, 'request' => $request], $context), $request, $protocol_version);
    }

    /**
     * The developer's concrete for the request, once the request is one the client declared: an elicitation mode it
     * declared (-32602 otherwise, as the elicitation page requires), tool-enabled sampling only with sampling.tools, and
     * from 2025-11-25 sampling whose tool results follow the sampling page's rules.
     */
    public static function concrete(Request $request, ClientConfiguration $configuration, string $protocol_version): Roots|Sampling|Elicitation
    {
        [$names, $base] = self::CAPABILITIES[$request->method];

        if($request->method === 'elicitation/create' && ! in_array($mode = $request->params->mode ?? 'form', self::elicitationModes($configuration, $protocol_version), true)) {
            throw new SuperconductorMCPException("This client does not support {$mode} elicitation.", RPCErrorCode::INVALID_PARAMS->value);
        }

        if($request->method === 'sampling/createMessage' && isset($request->params->tools) && ! $configuration->capabilities($protocol_version)->offersCapability('sampling.tools')) {
            throw new SuperconductorMCPException('This client does not support tool use in sampling.', RPCErrorCode::INVALID_PARAMS->value);
        }

        if($request->method === 'sampling/createMessage' && in_array($protocol_version, ['2025-11-25', '2026-07-28'], true)
            && ($problem = self::toolResultProblem($request->toArray()['params']['messages'] ?? []))) {
            throw new SuperconductorMCPException($problem, RPCErrorCode::INVALID_PARAMS->value);
        }

        $class = $configuration->$names();

        if(! is_string($class) || ! is_subclass_of($class, $base)) {
            // The developer's mistake, not the server's: reported, and the server only learns something went wrong.
            throw new MCPMechanismException($configuration::class." declares {$names} but names no {$base} to answer {$request->method}.");
        }

        return app($class);
    }

    /**
     * roots/list from a client that declares roots but names no Roots concrete: it exposes no roots, so the answer is
     * an empty list in the request's version.
     */
    public static function withoutConcrete(Request $request, ClientConfiguration $configuration, string $protocol_version): Result|InputResponse|null
    {
        if($request->method !== 'roots/list' || ! is_null($configuration->roots())) return null;

        return new ('Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\Messaging\\Roots\\ListRootsResult')([]);
    }

    /**
     * Runs the concrete towards its outcome and hands it to $deliver: the result, or the error it failed with. A
     * concrete that runs async does so when $may_defer, since $deliver can wait; otherwise it runs in line.
     *
     * @param Closure(Result|InputResponse|MCPError): void $deliver
     */
    public static function run(Roots|Sampling|Elicitation $concrete, Request $request, ClientConfiguration $configuration, string $protocol_version, ?RequestContext $context, Closure $deliver, bool $may_defer): void
    {
        $completion = new Completion(
            fn(mixed $output) => self::checked($output, $request, $protocol_version),
            fn(Throwable $e) => self::error($e, $protocol_version),
            $deliver,
        );

        $async = $concrete->wantsAsync() && $may_defer;

        if($async) $concrete->deferTo($completion);

        try {
            $output = Invoker::call($concrete, 'handle', $configuration, $protocol_version, [$request::class => $request, 'request' => $request], $context);
        } catch(Throwable $e) {
            $completion->fail($e);
            return;
        }

        if(! $async) $completion->finish($output);
    }

    /**
     * The concrete's output, if it is the result its request's version defines: on 2026-07-28 an input response.
     */
    private static function checked(mixed $output, Request $request, string $protocol_version): Result|InputResponse
    {
        $class = 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\'.self::CAPABILITIES[$request->method][2];

        if(! $output instanceof $class) {
            throw new MCPMechanismException("{$request->method} must answer with {$class}; got ".get_debug_type($output).'.');
        }

        return $request->method === 'roots/list' ? self::exposable($output) : $output;
    }

    /**
     * The roots the client may expose. The roots page requires a root's uri to be a file:// URI and the client to
     * validate every root URI against path traversal: a root that fails is not exposed, and is reported.
     */
    private static function exposable(Result|InputResponse $roots): Result|InputResponse
    {
        $valid = array_values(array_filter($roots->roots, function(object $root): bool {
            $parts = parse_url($root->uri);
            $path = isset($parts['path']) ? rawurldecode($parts['path']) : null;
            $safe = ($parts['scheme'] ?? null) === 'file' && is_string($path) && str_starts_with($path, '/')
                && ! str_contains($path, "\0") && ! array_intersect(explode('/', $path), ['.', '..']);

            if(! $safe) report(new MCPMechanismException("Root [{$root->uri}] is not exposed: a root must be a file:// URI with an absolute path and no . or .. segments."));

            return $safe;
        }));

        if(count($valid) === count($roots->roots)) return $roots;

        return $roots instanceof Result ? new ($roots::class)($valid, $roots->_meta) : new ($roots::class)($valid);
    }

    /**
     * Why a sampling request's tool results break the sampling page's rules, or null: a user message with tool results
     * may hold nothing else, and every assistant tool use must be answered by a tool result in the user message right
     * after it. The page answers both with -32602.
     *
     * @param list<array<string, mixed>> $messages
     */
    private static function toolResultProblem(array $messages): ?string
    {
        $blocks = fn(?array $message): array => isset($message['content']['type']) ? [$message['content']] : array_values((array) ($message['content'] ?? []));
        $messages = array_values($messages);

        foreach($messages as $at => $message)
        {
            $types = array_column($blocks($message), 'type');

            if(($message['role'] ?? null) === 'user' && in_array('tool_result', $types, true) && array_diff($types, ['tool_result']) !== []) {
                return 'Tool results are mixed with other content in a user message.';
            }

            $uses = array_column(array_filter($blocks($message), fn(array $block) => ($block['type'] ?? null) === 'tool_use'), 'id');

            if(($message['role'] ?? null) !== 'assistant' || $uses === []) continue;

            $next = $messages[$at + 1] ?? null;
            $answered = ($next['role'] ?? null) === 'user' ? array_column($blocks($next), 'toolUseId') : [];

            if(array_diff($uses, $answered) !== []) return 'A tool use has no tool result in the user message after it.';
        }

        return null;
    }

    /**
     * Why the concrete failed, as the version's error: its own code when the version knows it, otherwise an internal
     * error, reported.
     */
    private static function error(Throwable $e, string $protocol_version): MCPError
    {
        $class = 'Fabricate\\Schema\\V'.str_replace('-', '', $protocol_version).'\\Messaging\\MCPError';

        if($e instanceof SuperconductorMCPException) {
            try {
                return new $class($e->getCode(), $e->getMessage(), $e->data);
            } catch(SuperconductorMCPException) {
                // Not a code this version has.
            }
        }

        report($e);

        return new $class(RPCErrorCode::INTERNAL_ERROR->value, 'Internal error');
    }

    /**
     * The elicitation modes the client declared: on 2025-06-18 only form; from 2025-11-25 the ones named, and form when
     * none is, as the elicitation page reads an empty declaration.
     *
     * @return list<string>
     */
    private static function elicitationModes(ClientConfiguration $configuration, string $protocol_version): array
    {
        if($protocol_version === '2025-06-18') return ['form'];

        $modes = array_keys(array_intersect_key($configuration->elicitationConfig() ?? [], ['form' => true, 'url' => true]));

        return $modes ?: ['form'];
    }

    /**
     * On 2025-11-25, the task a request asked for, when the client offers tasks for its method.
     */
    private function taskFor(Request $request, ClientConfiguration $configuration, string $protocol_version, ?RequestContext $context): ?TaskRecord
    {
        if($protocol_version !== '2025-11-25' || ! isset($request->params->task)) return null;

        if(! $configuration->capabilities($protocol_version)->offersCapability('tasks.requests.'.str_replace('/', '.', $request->method))) return null;

        return $configuration->taskStore()->create($request->params->task->ttl, $context?->auth_identifier);
    }
}
