<?php

namespace Fabricate\Schema\Common\Concerns;

trait HasVersion
{
    public function version(): string
    {
        return $this->version;
    }
}
