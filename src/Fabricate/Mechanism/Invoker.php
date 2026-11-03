<?php

namespace Fabricate\Mechanism;

use Error;
use Fabricate\Contracts\Mechanism\EntityConfiguration;

/**
 * Calls a method of a tool, prompt or resource through the container. A parameter typed as the server configuration
 * (the contract, the base class or the concrete) gets the configuration, $protocol_version gets the version, RequestContext the request being answered when there is one, and any
 * named values given are passed by name; anything else is resolved from the container.
 */
final class Invoker
{
    /**
     * @param array<string, mixed> $named
     */
    public static function call(object $target, string $method, EntityConfiguration $entity_configuration, string $protocol_version, array $named = [], ?RequestContext $context = null): mixed
    {
        // The configuration answers to its own class and every class and interface above it.
        $configuration = array_fill_keys([$entity_configuration::class, ...class_parents($entity_configuration), ...class_implements($entity_configuration)], $entity_configuration);

        return app()->call([$target, $method], $named + ($context ? [RequestContext::class => $context] : []) + $configuration + [
            'protocol_version' => $protocol_version,
        ]);
    }

    /**
     * Why a constructor refused the arguments, without the server file and line PHP names in the message.
     */
    public static function argumentError(Error $e): string
    {
        return preg_replace('/(, called)? in \S+ on line \d+/', '', $e->getMessage());
    }
}
