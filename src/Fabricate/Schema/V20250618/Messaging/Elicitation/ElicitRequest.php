<?php

namespace Fabricate\Schema\V20250618\Messaging\Elicitation;

use Fabricate\Contracts\Schema\Messaging\Elicitation\ElicitRequest as ElicitContract;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20250618\Concerns\PrimitiveSchemaDefinition;
use Fabricate\Schema\V20250618\Messaging\Request;

final readonly class ElicitRequest extends Request implements ElicitContract, ServerRequest
{
    /**
     * @param string $message
     * @param array<string,PrimitiveSchemaDefinition> $properties
     * @param string[]|null $required
     * @param array|null $_meta
     * @param ProgressToken|null $progress_token
     */
    public function __construct(
        string $message,
        array $properties,
        ?array $required = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null,
    ) {
        $requested_schema = [
            'type' => 'object',
            'properties' => array_map(fn(PrimitiveSchemaDefinition $property) => $property->toArray(), $properties) ?: (object) [],
        ];

        if(isset($required)) $requested_schema['required'] = $required;

        parent::__construct(
            'elicitation/create',
            ['message' => $message, 'requestedSchema' => $requested_schema],
            $_meta,
            $progress_token,
        );
    }
}
