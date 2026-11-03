<?php

namespace Fabricate\Mechanism\Hydration;

/**
 * Maps the capabilities objects peers send to the constructor flags of the ClientCapabilities and
 * ServerCapabilities classes. Each returns every flag any version declares; the capabilities factory
 * keeps only the ones the requested version's constructor names.
 */
final class CapabilityArguments
{
    /**
     * @param array<string, mixed> $capabilities The wire capabilities object of an initialize request.
     * @return array<string, bool|array<string, mixed>|null>
     */
    public static function client(array $capabilities): array
    {
        $sampling = $capabilities['sampling'] ?? null;
        $roots = $capabilities['roots'] ?? null;
        $elicitation = $capabilities['elicitation'] ?? null;
        $tasks = $capabilities['tasks'] ?? null;
        $task_requests = is_array($tasks) ? ($tasks['requests'] ?? null) : null;

        return [
            'sampling' => is_array($sampling),
            'roots' => is_array($roots),
            'elicitation' => is_array($elicitation),
            'tasks' => is_array($tasks),
            'announce_when_roots_change' => (bool) ($roots['listChanged'] ?? false),
            'support_sampling_context' => isset($sampling['context']),
            'support_sampling_tools' => isset($sampling['tools']),
            'support_elicitation_forms' => isset($elicitation['form']),
            'support_elicitation_urls' => isset($elicitation['url']),
            'support_tasks_list' => isset($tasks['list']),
            'support_tasks_cancel' => isset($tasks['cancel']),
            'support_tasks_requests' => is_array($task_requests),
            'support_tasks_sampling_requests' => isset($task_requests['sampling']),
            'support_tasks_sampling_create_message_requests' => isset($task_requests['sampling']['createMessage']),
            'support_tasks_elicitation_requests' => isset($task_requests['elicitation']),
            'support_tasks_elicitation_create_requests' => isset($task_requests['elicitation']['create']),
            'experimental' => is_array($capabilities['experimental'] ?? null) ? $capabilities['experimental'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $capabilities The wire capabilities object of an initialize result.
     * @return array<string, bool|array<string, mixed>|null>
     */
    public static function server(array $capabilities): array
    {
        $tools = $capabilities['tools'] ?? null;
        $resources = $capabilities['resources'] ?? null;
        $prompts = $capabilities['prompts'] ?? null;
        $tasks = $capabilities['tasks'] ?? null;
        $task_requests = is_array($tasks) ? ($tasks['requests'] ?? null) : null;

        return [
            'tools' => is_array($tools),
            'resources' => is_array($resources),
            'prompts' => is_array($prompts),
            'logging' => is_array($capabilities['logging'] ?? null),
            'completions' => is_array($capabilities['completions'] ?? null),
            'tasks' => is_array($tasks),
            'announce_when_tools_change' => (bool) ($tools['listChanged'] ?? false),
            'announce_individual_resource_updates' => (bool) ($resources['subscribe'] ?? false),
            'announce_when_resources_change' => (bool) ($resources['listChanged'] ?? false),
            'announce_when_prompts_change' => (bool) ($prompts['listChanged'] ?? false),
            'support_tasks_list' => isset($tasks['list']),
            'support_tasks_cancel' => isset($tasks['cancel']),
            'support_tasks_requests' => is_array($task_requests),
            'support_tasks_tool_requests' => isset($task_requests['tools']),
            'support_tasks_tool_call_requests' => isset($task_requests['tools']['call']),
            'experimental' => is_array($capabilities['experimental'] ?? null) ? $capabilities['experimental'] : null,
        ];
    }
}
