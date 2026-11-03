<?php

namespace Fabricate\Schema\V20250618\Messaging\Resources;

use Fabricate\Contracts\Schema\Messaging\Resources\ReadResourceResult as ReadResourceContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;
use Fabricate\Schema\V20250618\Capabilities\Resources\BlobResourceContents;
use Fabricate\Schema\V20250618\Capabilities\Resources\TextResourceContents;
use Fabricate\Schema\V20250618\Messaging\Result;

final readonly class ReadResourceResult extends Result implements ReadResourceContract, ServerResult
{
    /**
     * @param (TextResourceContents|BlobResourceContents)[] $contents
     * @param array|null $_meta
     */
    public function __construct(
        public array $contents,
        ?array $_meta = null,
    ) {
        parent::__construct($_meta);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['contents'] = array_map(fn(TextResourceContents|BlobResourceContents $content) => $content->toArray(), $this->contents);

        return $results;
    }
}
