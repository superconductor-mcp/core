<?php

namespace Fabricate\Schema\V20260728\Messaging\Params;

use Fabricate\Schema\V20260728\Concerns\ElicitRequestParams;
use Fabricate\Schema\V20260728\Concerns\PrimitiveSchemaDefinition;

final readonly class ElicitRequestFormParams implements ElicitRequestParams
{
    public string $mode;
    public array $requestedSchema;

    /**
     * @param array<string,PrimitiveSchemaDefinition> $properties
     * @param string[]|null $required
     */
    public function __construct(
        public string $message,
        array $properties,
        ?array $required = null,
        ?string $schema_dialect = null,
    ) {
        $this->mode = 'form';

        $schema = ['type' => 'object'];
        if(isset($schema_dialect)) $schema['$schema'] = $schema_dialect;
        $schema['properties'] = array_map(fn(PrimitiveSchemaDefinition $property) => $property->toArray(), $properties) ?: (object) [];
        if(isset($required)) $schema['required'] = $required;

        $this->requestedSchema = $schema;
    }

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'message' => $this->message,
            'requestedSchema' => $this->requestedSchema,
        ];
    }
}
