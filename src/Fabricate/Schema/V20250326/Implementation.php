<?php

namespace Fabricate\Schema\V20250326;

use Fabricate\Contracts\Schema\Contracts\Implementation as ImplementationContract;
use Fabricate\Schema\Common\Concerns\HasVersion;

readonly class Implementation implements ImplementationContract
{
    use HasVersion;

    public function __construct(
        public string $name,
        public string $version,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
        ];
    }
}
