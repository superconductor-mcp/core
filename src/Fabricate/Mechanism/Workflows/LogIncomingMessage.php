<?php

declare(strict_types=1);

namespace Fabricate\Mechanism\Workflows;

use Fabricate\NutsAndBolts\Messages\MCPRequest;
use Fabricate\NutsAndBolts\Messages\RoutedMCPMessage;
use Throwable;
use Voyager\Workflows\AsyncNode;
use Voyager\Workflows\SharedBag;

class LogIncomingMessage extends AsyncNode
{
    public function __construct(protected readonly RoutedMCPMessage $message)
    {
        parent::__construct();
    }

    // dispatched without anyone waiting on it: hand it to the runtime and keep going
    public function dispatch(SharedBag $shared): void
    {
        $this->runtime()->async(fn () => $this->runAsync($shared));
    }

    public function prepAsync(SharedBag $shared): mixed
    {
        return $this->message;
    }

    public function execAsync(mixed $prepRes): mixed
    {
        /** @var RoutedMCPMessage $prepRes */
        app('log')->info('mcp.incoming '.$prepRes->method, [
            'id' => $prepRes instanceof MCPRequest ? $prepRes->id : null,
            'params' => $prepRes->params,
        ]);

        return null;
    }

    // a log that cannot be written must never take the server down with it
    public function execFallbackAsync(mixed $prepRes, Throwable $e): mixed
    {
        return null;
    }
}
