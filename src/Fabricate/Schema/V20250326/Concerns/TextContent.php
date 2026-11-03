<?php

namespace Fabricate\Schema\V20250326\Concerns;

use Fabricate\Contracts\Schema\Contracts\TextContent as TextContract;

readonly class TextContent implements TextContract
{
    public string $type;

    public function __construct(
        public string $text,
        public ?Annotations $annotations = null,
    ) {
        $this->type = 'text';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'text' => $this->text,
        ];

        if(isset($this->annotations)) $results['annotations'] = $this->annotations->toArray() ?: (object) [];

        return $results;
    }
}
