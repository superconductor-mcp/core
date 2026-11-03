<?php

namespace Fabricate\Schema\Common\Messaging;

use Fabricate\Contracts\Schema\Messaging\MCPError as ErrorContract;

abstract readonly class MCPError implements ErrorContract
{
    public function __construct(
        public int $code,
        public string $message,
        public mixed $data = null,
    ) {}

    public function toArray(): array
    {
        $results = [
            'code' => $this->code,
            'message' => $this->message,
        ];

        if(isset($this->data)) $results['data'] = $this->data;

        return $results;
    }
}
