<?php

namespace Fabricate\Schema\V20241105\Concerns;

use Fabricate\Contracts\Schema\Concerns\Role;
use Fabricate\Contracts\Schema\Contracts\TextContent as TextContract;

readonly class TextContent extends Annotated implements TextContract
{
    public string $type;

    /**
     * @param Role[]|null $audience
     */
    public function __construct(
        public string $text,
        ?array $audience = null,
        int|float|null $priority = null,
    ) {
        parent::__construct($audience, $priority);

        $this->type = 'text';
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['type'] = $this->type;
        $results['text'] = $this->text;

        return $results;
    }
}
