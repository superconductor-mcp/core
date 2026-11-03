<?php

namespace Fabricate\Schema\Common\Concerns;

use Fabricate\Contracts\Schema\Contracts\Icon as IconContract;
use Fabricate\Contracts\Schema\IconTheme;

readonly class Icon implements IconContract
{
    use HasIcons;

    public function __construct(
        public string $src,
        public ?string $mimeType = null,
        public ?array $sizes = null,
        public ?IconTheme $theme = null,
    ) {}

    public function toArray(): array
    {
        $results = ['src' => $this->src];

        if(isset($this->mimeType)) $results['mimeType'] = $this->mimeType;
        if(isset($this->sizes)) $results['sizes'] = $this->sizes;
        if(isset($this->theme)) $results['theme'] = $this->theme->value;

        return $results;
    }
}
