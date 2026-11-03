<?php

namespace Fabricate\Schema\V20250618\Messaging\Prompts;

use Fabricate\Contracts\Schema\Messaging\ClientRequest;
use Fabricate\Contracts\Schema\Messaging\Prompts\GetPromptRequest as GetPromptContract;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Messaging\Request;

final readonly class GetPromptRequest extends Request implements GetPromptContract, ClientRequest
{
    private string $name;

    private ?array $arguments;

    /**
     * @param array<string,string>|null $arguments
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

        parent::__construct('prompts/get',$params, $_meta, $progress_token);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function arguments(): ?array
    {
        return $this->arguments;
    }
}
