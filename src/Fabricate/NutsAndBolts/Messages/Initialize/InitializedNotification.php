<?php

namespace Fabricate\NutsAndBolts\Messages\Initialize;

use Fabricate\NutsAndBolts\Messages\MCPNotification;
use Voyager\Contracts\Signals\NamedSignal;

readonly class InitializedNotification extends MCPNotification implements NamedSignal
{
    public function __construct()
    {
        parent::__construct('notifications/initialized');
    }

    public function name(): string
    {
        return $this->method;
    }

    public function toJson(): string
    {
        return json_encode(['jsonrpc' => '2.0', 'method' => $this->method]);
    }
}
