<?php

namespace Fabricate\Mechanism;

use InvalidArgumentException;
use Fabricate\NutsAndBolts\Icon;
use Fabricate\Contracts\Mechanism\MCPEntity as EntityContract;

abstract class MCPEntity implements EntityContract
{
    protected array $entity_info = [];
    protected array $entity_capabilities = [];

    public function entityInfo(): array
    {
        $info = $this->entity_info;

        // 2025-11-25: icons, description and websiteUrl ride along with name and version; checked as a definition's are
        foreach (['description', 'websiteUrl'] as $key) {
            if (array_key_exists($key, $info) && ! is_string($info[$key])) {
                throw new InvalidArgumentException("entity info [{$key}] must be a string.");
            }
        }

        if (array_key_exists('icons', $info)) {
            $info['icons'] = Icon::render(Icon::list($info['icons'])) ?? [];
        }

        return $info;
    }
}
