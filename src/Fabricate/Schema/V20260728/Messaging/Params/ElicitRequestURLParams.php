<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Concerns\ElicitRequestParams;

final readonly class ElicitRequestURLParams implements ElicitRequestParams
{
    public string $mode;

    public function __construct(
        public string $message,
        public string $url,
    ) {
        $this->mode = 'url';
    }

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
