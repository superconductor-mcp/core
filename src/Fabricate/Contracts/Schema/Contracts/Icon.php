<?php

namespace Fabricate\Contracts\Schema\Contracts;

use Fabricate\Contracts\Schema\IconTheme;
use Voyager\Contracts\NutsAndBolts\Arrayable;

interface Icon extends Arrayable
{
    public function src(): string;

    public function mimeType(): ?string;

    /**
     * @return string[]|null
     */
    public function sizes(): ?array;

    public function theme(): ?IconTheme;
}
