<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;

final readonly class GetPromptRequestParams extends RequestParams
{
    /**
     * @param array<string,string>|null $arguments
     */
    public function __construct(
        public string $name,
        public ?array $arguments = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        parent::__construct($_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['name'] = $this->name;
        if(isset($this->arguments)) $results['arguments'] = $this->arguments ?: (object) [];

        return $results;
    }
}
