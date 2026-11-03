<?php

namespace Fabricate\Schema\V20250618;

use Fabricate\Contracts\Schema\Contracts\Implementation as ImplementationContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\Common\Concerns\HasVersion;

readonly class Implementation extends BaseMetaData implements ImplementationContract
{
    use HasVersion;

    public function __construct(
        string $name,
        public string $version,
        ?string $title = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['version'] = $this->version;

        return $results;
    }
}
