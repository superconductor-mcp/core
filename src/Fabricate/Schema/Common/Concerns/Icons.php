<?php

namespace Fabricate\Schema\Common\Concerns;

readonly class Icons
{
    /**
     * @param Icon[] $icons
     */
    public function __construct(
        public array $icons = [],

    ) {}
}
