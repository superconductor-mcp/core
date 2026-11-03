<?php

namespace Fabricate\Core\Rpc\Procedures\Resources;

use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\Attributes\CompatibleVersions;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscriptionsListenRequest;
use Fabricate\Contracts\Schema\Messaging\Resources\SubscriptionsListenResult;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Core\Rpc\Procedures\Procedure;
use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Mechanism\Signals\ListChanged;
use Fabricate\Mechanism\Signals\Subscribed;
use Fabricate\Schema\V20260728\Capabilities\Resources\SubscriptionFilter;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use Fabricate\Server\ServerConfiguration as ServerConfigurationBase;
use Fabricate\Schema\V20260728\Messaging\MetaObjects\NotificationMetaObject;
use Fabricate\Schema\V20260728\Messaging\Params\SubscriptionsAcknowledgedNotificationParams;
use Fabricate\Schema\V20260728\Messaging\Resources\SubscriptionsAcknowledgedNotification;

#[CompatibleVersions('2026-07-28')]
class SubscriptionsProcedure extends Procedure
{
    /**
     * Opens a subscription that carries the change notifications the client asked for and this server can send: list
     * changes for each capability that declared listChanged, and updates to the requested resources when it declared
     * resources.subscribe, and the status of the named tasks this server has, for a client that declared the tasks
     * extension (one that didn't gets -32021 for asking). notifications/subscriptions/acknowledged goes first, naming that subset; the request stays
     * open until the client cancels it or the server ends it.
     */
    public function handle(SubscriptionsListenRequest $request, ServerConfiguration $entity_configuration, string $protocol_version, ?RequestContext $context = null): SubscriptionsListenResult|Deferred
    {
        if(is_null($context)) {
            throw new SuperconductorMCPException('subscriptions/listen needs a session to deliver its notifications on.', RPCErrorCode::INTERNAL_ERROR->value);
        }

        $asked = $request->notifications();

        if($asked->taskIds && ! isset($context->client_capabilities['extensions'][ServerConfigurationBase::TASKS_EXTENSION])) {
            throw new SuperconductorMCPException('Task notifications need the '.ServerConfigurationBase::TASKS_EXTENSION.' extension.', MCPErrorCode::MISSING_REQUIRED_CLIENT_CAPABILITY->value, data: ['requiredCapabilities' => ['extensions' => [ServerConfigurationBase::TASKS_EXTENSION => (object) []]]]);
        }

        $agreed = new SubscriptionFilter(
            $asked->toolsListChanged && $entity_configuration->announcesListChanges(ListChanged::TOOLS) ? true : null,
            $asked->promptsListChanged && $entity_configuration->announcesListChanges(ListChanged::PROMPTS) ? true : null,
            $asked->resourcesListChanged && $entity_configuration->announcesListChanges(ListChanged::RESOURCES) ? true : null,
            $asked->resourceSubscriptions && ($entity_configuration->resourcesConfig()['subscribe'] ?? false) ? array_values($asked->resourceSubscriptions) : null,
            $this->knownTasks($asked->taskIds ?? [], $entity_configuration, $context) ?: null,
        );

        $context->notify(new SubscriptionsAcknowledgedNotification(new SubscriptionsAcknowledgedNotificationParams($agreed, new NotificationMetaObject($context->id))));
        signal(new Subscribed($context, $agreed));

        return new Deferred();
    }

    /**
     * Of the task ids a subscription asks about, those of tasks this server runs, still has, and created for whoever asks.
     *
     * @param list<string> $ids
     * @return list<string>
     */
    private function knownTasks(array $ids, ServerConfiguration $entity_configuration, RequestContext $context): array
    {
        if(! $entity_configuration instanceof ServerConfigurationBase || ! ($entity_configuration->tasksConfig()['requests']['tools']['call'] ?? false)) return [];

        return array_values(array_filter($ids, fn(mixed $id) => is_string($id) && $entity_configuration->taskStore()->find($id, $context->auth_identifier)));
    }
}
