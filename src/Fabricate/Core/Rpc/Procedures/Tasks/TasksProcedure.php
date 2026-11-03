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
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\Paginator;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Schema\V20251125\Messaging\MCPError as TaskError;
use Fabricate\Schema\V20251125\Messaging\Tasks;
use Fabricate\Server\Tasks\TaskRecord;
use Fabricate\Server\Tasks\TaskStore;

/**
 * The 2025-11-25 tasks a server ran for a session's requests. The store lives on the server configuration, so a
 * client receiving tasks has none to report.
 */
#[CompatibleVersions('2025-11-25')]
#[RequiresCapability('tasks')]
class TasksProcedure extends Procedure
{
    public function get(GetTaskRequest $request, EntityConfiguration $entity_configuration, string $protocol_version): GetTaskResult
    {
        return new Tasks\GetTaskResult($this->task($request->taskId(), $entity_configuration)->toSchema());
    }

    /**
     * The task's outcome: the result of the request it ran, or that request's error. While the task is still working
     * the answer waits for it, so it comes later as mail.
     */
    public function result(GetTaskPayloadRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): GetTaskPayloadResult|Deferred
    {
        $task = $this->task($request->taskId(), $entity_configuration);

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
    public function list(ListTasksRequest $request, EntityConfiguration $entity_configuration, string $protocol_version): ListTasksResult
    {
        [$tasks, $next_cursor] = Paginator::page($this->store($entity_configuration)?->all() ?? [], $request->cursor(), (int) config('mcp.pagination.page_size', 50));

        return new Tasks\ListTasksResult(array_map(fn(TaskRecord $task) => $task->toSchema(), $tasks), $next_cursor);
    }

    /**
     * Cancels a working task. Its work isn't interrupted, but what it produces afterwards is dropped.
     */
    #[RequiresCapability('tasks.cancel')]
    public function cancel(CancelTaskRequest $request, EntityConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): CancelTaskResult
    {
        $task = $this->task($request->taskId(), $entity_configuration);

        if($task->terminal()) {
            throw new SuperconductorMCPException("Task [{$task->id}] is already {$task->status} and can't be cancelled.", RPCErrorCode::INVALID_PARAMS->value);
        }

        $task->cancel();
        $context?->notify($task->statusNotification());

        return new Tasks\CancelTaskResult($task->toSchema());
    }

    private function task(string $id, EntityConfiguration $entity_configuration): TaskRecord
    {
        return $this->store($entity_configuration)?->find($id)
            ?? throw new SuperconductorMCPException("Task not found: [{$id}].", RPCErrorCode::INVALID_PARAMS->value);
    }

    private function store(EntityConfiguration $entity_configuration): ?TaskStore
    {
        return $entity_configuration instanceof ServerConfiguration ? $entity_configuration->taskStore() : null;
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
