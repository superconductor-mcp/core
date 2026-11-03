<?php

namespace Fabricate\Core\Capabilities\Tools;

use Fabricate\Contracts\Mechanism\Attributes\ToolCall;
use Fabricate\Contracts\Server\ServerConfiguration;
use Fabricate\Mechanism\Tool;

#[ToolCall('search_prompts', 'Search the prompts this server offers by name or description.')]
final class SearchPromptsTool extends Tool
{
    public function __construct(
        public readonly string $query = '',
    ) {}

    public function handle(ServerConfiguration $server, Catalog $catalog): string
    {
        return json_encode($catalog->prompts($server, $this->query), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
