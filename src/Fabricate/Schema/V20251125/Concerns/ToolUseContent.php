<?php

namespace Fabricate\Schema\V20251125\Concerns;

readonly class ToolUseContent implements SamplingMessageContentBlock
{
    public string $type;

    /**
     * @param array<string,mixed> $input
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $input,
        public ?array $_meta = null,
    ) {
        $this->type = 'tool_use';
    }

    public function toArray(): array
    {
        $results = [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
            'input' => $this->input ?: (object) [],
        ];

        if(isset($this->_meta)) $results['_meta'] = $this->_meta ?: (object) [];

        return $results;
    }
}
