<?php

namespace Fabricate\Core\Capabilities\Tools;

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Mechanism\Tool;

#[ToolCall('list_tools', 'List the tools this server offers, with their input schemas.')]
final class ListToolsTool extends Tool
{
    public function handle(ServerConfiguration $server, Catalog $catalog): string
    {
        return json_encode($catalog->tools($server), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
