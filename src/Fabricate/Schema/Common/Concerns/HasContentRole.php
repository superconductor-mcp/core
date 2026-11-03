<?php

namespace Fabricate\Schema\Common\Concerns;

use Fabricate\Contracts\Schema\Concerns\Role;

trait HasContentRole
{
    public function role(): Role
    {
        return $this->role;
    }

    public function content(): mixed
    {
        return $this->content;
    }
}
