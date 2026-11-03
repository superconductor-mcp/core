<?php

namespace Fabricate\Schema\V20251125\Messaging\Params;

use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20251125\Concerns\ElicitRequestParams;
use Fabricate\Schema\V20251125\Concerns\PrimitiveSchemaDefinition;
use Fabricate\Schema\V20251125\TaskMetadata;

final readonly class ElicitRequestFormParams extends TaskAugmentedRequestParams implements ElicitRequestParams
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
        ?TaskMetadata $task = null,
        ?array $_meta = null,
        ?ProgressToken $progress_token = null
    ) {
        $this->mode = 'form';

        $schema = ['type' => 'object'];
        if(isset($schema_dialect)) $schema['$schema'] = $schema_dialect;
        $schema['properties'] = array_map(fn(PrimitiveSchemaDefinition $property) => $property->toArray(), $properties) ?: (object) [];
        if(isset($required)) $schema['required'] = $required;

        $this->requestedSchema = $schema;

        parent::__construct($task, $_meta, $progress_token);
    }

    public function toArray(): array
    {
        $results = parent::toArray();

        $results['mode'] = $this->mode;
        $results['message'] = $this->message;
        $results['requestedSchema'] = $this->requestedSchema;

        return $results;
    }
}
