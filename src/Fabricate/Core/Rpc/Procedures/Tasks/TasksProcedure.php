<?php

namespace Fabricate\Core\Rpc\Procedures\Tasks;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Mechanism\Attributes\RequiresCapability;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Tasks\CancelTaskRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\CancelTaskResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskPayloadRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskPayloadResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\GetTaskResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\ListTasksRequest;
use Fabricate\Contracts\Schema\Messaging\Tasks\ListTasksResult;
use Fabricate\Contracts\Schema\Messaging\Tasks\UpdateTaskRequest;
use Fabricate\Contracts\Schema\Messaging\EmptyResult;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Contracts\Client\ClientConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Paginator;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Schema\V20251125\Messaging\MCPError as TaskError;
use Fabricate\Schema\V20251125\Messaging\Tasks;
use Fabricate\Schema\V20260728;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Server\ServerConfiguration as ServerConfigurationBase;
use Fabricate\Server\Tasks\TaskRecord;
use Fabricate\Server\Tasks\TaskStore;

/**
 * The tasks a server ran for its requests: 2025-11-25's tasks utility, and from 2026-07-28 the io.modelcontextprotocol/
 * tasks extension, which polls with tasks/get, takes input through tasks/update, and has no tasks/list or tasks/result.
 * The store lives on the configuration: a server's holds its tool calls, a 2025-11-25 client's its sampling and
 * elicitation run as tasks.
 */
#[CompatibleVersions('2025-11-25')]
#[RequiresCapability('tasks')]
class TasksProcedure extends Procedure
{
    #[CompatibleVersions('2025-11-25', '2026-07-28')]
    public function get(GetTaskRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): GetTaskResult
    {
        if($protocol_version === '2026-07-28') {
            $this->extensionNegotiated($entity_configuration, $context);

            return new V20260728\Messaging\Tasks\GetTaskResult($this->task($request->taskId(), $entity_configuration, $context)->toDetailed(), $this->resultMeta($entity_configuration, $protocol_version));
        }

        return new Tasks\GetTaskResult($this->task($request->taskId(), $entity_configuration, $context)->toSchema());
    }

    /**
     * Answers a task's outstanding inputRequests. Keys that aren't outstanding are ignored; once every outstanding one
     * is answered the task resumes. Acknowledged with an empty result.
     */
    #[CompatibleVersions('2026-07-28')]
    public function update(UpdateTaskRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): EmptyResult
    {
        $this->extensionNegotiated($entity_configuration, $context);

        $this->task($request->taskId(), $entity_configuration, $context)->supply($request->inputResponses());

        return new V20260728\Messaging\EmptyResult(_meta: $this->resultMeta($entity_configuration, $protocol_version));
    }

    /**
     * The task's outcome: the result of the request it ran, or that request's error. While the task is still working
     * the answer waits for it, so it comes later as mail.
     */
    public function result(GetTaskPayloadRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): GetTaskPayloadResult|Deferred
    {
        $task = $this->task($request->taskId(), $entity_configuration, $context);

        if($task->terminal()) {
            $outcome = $this->outcome($task);

            if($outcome instanceof MCPError) {
                ['code' => $code, 'message' => $message] = $outcome->toArray();

                throw new SuperconductorMCPException($message, $code);
            }

            return $outcome;
        }

        if(is_null($context)) {
            throw new SuperconductorMCPException("Task [{$task->id}] is still working.", RPCErrorCode::INVALID_REQUEST->value);
        }

        $task->whenSettled(fn(TaskRecord $settled) => $context->reply($this->outcome($settled)));

        return new Deferred();
    }

    #[RequiresCapability('tasks.list')]
    public function list(ListTasksRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): ListTasksResult
    {
        $own = array_values(array_filter($this->store($entity_configuration)?->all() ?? [], fn(TaskRecord $task) => $task->auth_identifier === $context?->auth_identifier));
        [$tasks, $next_cursor] = Paginator::page($own, $request->cursor(), (int) config('mcp.pagination.page_size', 50));

        return new Tasks\ListTasksResult(array_map(fn(TaskRecord $task) => $task->toSchema(), $tasks), $next_cursor);
    }

    /**
     * Cancels a working task. Its work isn't interrupted, but what it produces afterwards is dropped.
     */
    #[CompatibleVersions('2025-11-25', '2026-07-28')]
    #[RequiresCapability('tasks.cancel')]
    public function cancel(CancelTaskRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): CancelTaskResult|EmptyResult
    {
        $task = $protocol_version === '2026-07-28'
            ? ($this->extensionNegotiated($entity_configuration, $context) ?? $this->task($request->taskId(), $entity_configuration, $context))
            : $this->task($request->taskId(), $entity_configuration, $context);

        // 2026-07-28 only asks for an acknowledgement; a task that already ended stays as it is.
        if($protocol_version === '2026-07-28') {
            $task->cancel();

            return new V20260728\Messaging\EmptyResult(_meta: $this->resultMeta($entity_configuration, $protocol_version));
        }

        if($task->terminal()) {
            throw new SuperconductorMCPException("Task [{$task->id}] is already {$task->status} and can't be cancelled.", RPCErrorCode::INVALID_PARAMS->value);
        }

        $task->cancel();
        $context?->notify($task->statusNotification());

        return new Tasks\CancelTaskResult($task->toSchema());
    }

    /**
     * The tasks extension is in play only if this server offers it and the request's client declared it; a client that
     * didn't gets -32021 naming it, and a server that doesn't run tasks has no such method.
     */
    private function extensionNegotiated(EntityConfiguration $entity_configuration, ?RequestContext $context): null
    {
        if(! ($entity_configuration instanceof ServerConfigurationBase) || ! ($entity_configuration->tasksConfig()['requests']['tools']['call'] ?? false)) {
            throw new SuperconductorMCPException('This server runs no tasks.', RPCErrorCode::METHOD_NOT_FOUND->value);
        }

        if(! isset($context?->client_capabilities['extensions'][ServerConfigurationBase::TASKS_EXTENSION])) {
            throw new SuperconductorMCPException('Tasks need the '.ServerConfigurationBase::TASKS_EXTENSION.' extension.', MCPErrorCode::MISSING_REQUIRED_CLIENT_CAPABILITY->value, data: ['requiredCapabilities' => ['extensions' => [ServerConfigurationBase::TASKS_EXTENSION => (object) []]]]);
        }

        return null;
    }

    private function task(string $id, EntityConfiguration $entity_configuration, ?RequestContext $context): TaskRecord
    {
        return $this->store($entity_configuration)?->find($id, $context?->auth_identifier)
            ?? throw new SuperconductorMCPException("Task not found: [{$id}].", RPCErrorCode::INVALID_PARAMS->value);
    }

    private function store(EntityConfiguration $entity_configuration): ?TaskStore
    {
        return $entity_configuration instanceof ServerConfiguration || $entity_configuration instanceof ClientConfiguration ? $entity_configuration->taskStore() : null;
    }

    /**
     * A settled task's outcome as tasks/result answers it, tied to the task by the related-task _meta.
     */
    private function outcome(TaskRecord $task): GetTaskPayloadResult|MCPError
    {
        if($task->status === TaskRecord::CANCELLED) {
            return new TaskError(RPCErrorCode::INVALID_PARAMS->value, "Task [{$task->id}] was cancelled.");
        }

        if($task->result instanceof MCPError) return $task->result;

        $payload = $task->result->toArray();
        unset($payload['_meta']);

        return new Tasks\GetTaskPayloadResult($payload, ['io.modelcontextprotocol/related-task' => ['taskId' => $task->id]]);
    }
}
