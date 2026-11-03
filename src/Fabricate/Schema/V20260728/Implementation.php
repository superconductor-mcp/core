<?php

namespace Fabricate\Schema\V20260728;

use Fabricate\Contracts\Schema\Contracts\Implementation as ImplementationContract;
use Fabricate\Schema\Common\Concerns\BaseMetaData;
use Fabricate\Schema\Common\Concerns\HasVersion;
use Fabricate\Schema\Common\Concerns\Icon;

readonly class Implementation extends BaseMetaData implements ImplementationContract
{
    use HasVersion;

    /**
     * @param Icon[]|null $icons
     */
    public function __construct(
        string $name,
        public string $version,
        public ?array $icons = null,
        public ?string $description = null,
        public ?string $websiteUrl = null,
        ?string $title = null,
    ) {
        parent::__construct($name, $title);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['version'] = $this->version;
        if(isset($this->icons)) $results['icons'] = array_map(fn(Icon $icon) => $icon->toArray(), $this->icons);
        if(isset($this->description)) $results['description'] = $this->description;
        if(isset($this->websiteUrl)) $results['websiteUrl'] = $this->websiteUrl;

        return $results;
    }
}
