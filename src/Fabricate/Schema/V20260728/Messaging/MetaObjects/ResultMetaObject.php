<?php

namespace Fabricate\Schema\V20260728\Messaging\MetaObjects;

use Fabricate\Schema\V20260728\Implementation;
use Fabricate\Schema\V20260728\MetaObject;

readonly class ResultMetaObject extends MetaObject
{
    /**
     * @param array<string,mixed> $record
     */
    public function __construct(
        ?Implementation $server_info = null,
        array $record = [],
    ) {
        if(isset($server_info)) {
            $record['io.modelcontextprotocol/serverInfo'] = $server_info->toArray();
        }

        parent::__construct($record);
    }
}
