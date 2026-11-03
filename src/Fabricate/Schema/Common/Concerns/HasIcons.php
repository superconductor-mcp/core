<?php

namespace Fabricate\Schema\Common\Concerns;

use Fabricate\Contracts\Schema\IconTheme;

trait HasIcons
{
    public function src(): string
    {
        return $this->src;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    public function sizes(): ?array
    {
        return $this->sizes;
    }

    public function theme(): ?IconTheme
    {
        return $this->theme;
    }
}
