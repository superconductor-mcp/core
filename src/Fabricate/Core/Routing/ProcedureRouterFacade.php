<?php

namespace Fabricate\Core\Routing;

use Fabricate\Mechanism\Deferred;
use Fabricate\Mechanism\RequestContext;
use Fabricate\Contracts\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Schema\Messaging\MCPError;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\Messaging\Result;

class ProcedureRouterFacade
{
    public static function group(string $name, callable $action): ProcedureRouter
    {
        return app(ProcedureRouter::class)->group($name, $action);
    }

    public static function procedure(string $name, string|callable $action): ProcedureRouter
    {
        return app(ProcedureRouter::class)->procedure($name, $action);
    }

    public static function dispatch(string $protocol_version, Request $request, EntityConfiguration $entity_configuration, ?RequestContext $context = null): Result|MCPError|Deferred
    {
        return app(ProcedureRouter::class)->dispatch($protocol_version, $request, $entity_configuration, $context);
    }
}
