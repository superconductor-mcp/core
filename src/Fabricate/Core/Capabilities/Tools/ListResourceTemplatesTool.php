<?php

namespace Fabricate\Core\Capabilities\Tools;

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Mechanism\Tool;

#[ToolCall('list_resource_templates', 'List the resource templates this server offers.')]
final class ListResourceTemplatesTool extends Tool
{
    public function handle(ServerConfiguration $server, Catalog $catalog): string
    {
        return json_encode($catalog->templates($server), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
