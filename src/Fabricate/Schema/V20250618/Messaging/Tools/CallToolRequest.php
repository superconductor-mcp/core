<?php

namespace Fabricate\Schema\V20250618\Messaging\Tools;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Tools\CallToolRequest as CallToolContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Messaging\Request;

final readonly class CallToolRequest extends Request implements CallToolContract, ClientRequest
{
    private string $name;

    private ?array $arguments;

    /**
     * @param array<string,mixed>|null $arguments
     */
    public function __construct(
        string $name,
        ?array $arguments = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null,
    ) {
        $this->name = $name;
        $this->arguments = $arguments;

        $params = ['name' => $name];
        if(isset($arguments)) $params['arguments'] = $arguments ?: (object) [];

        parent::__construct('tools/call', $params, $_meta, $progress_token);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function arguments(): ?array
    {
        return $this->arguments;
    }

    public function runsAsTask(): bool
    {
        return false;
    }

    public function taskTtl(): ?int
    {
        return null;
    }
}
