<?php

namespace Fabricate\Schema\V20260728\Messaging\MetaObjects;

use Fabricate\Contracts\Schema\LoggingLevel;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Schema\V20260728\Capabilities\ClientCapabilities;
use Fabricate\Schema\V20260728\Implementation;
use Fabricate\Schema\V20260728\MetaObject;
use JetBrains\PhpStorm\Deprecated;

readonly class RequestMetaObject extends MetaObject
{
    /**
     * @param array<string,mixed> $record
     */
    public function __construct(
        string $protocol_version,
        ClientCapabilities $client_capabilities,
        ?Implementation $client_info = null,
        ?ProgressToken $progress_token = null,
        #[Deprecated] ?LoggingLevel $logging_level = null,
        array $record = [],
    ) {
        $record['io.modelcontextprotocol/protocolVersion'] = $protocol_version;
        $record['io.modelcontextprotocol/clientCapabilities'] = $client_capabilities->toArray() ?: (object) [];

        if(isset($client_info)) $record['io.modelcontextprotocol/clientInfo'] = $client_info->toArray();
        if(isset($progress_token)) $record['progressToken'] = $progress_token->token;
        if(isset($logging_level)) $record['io.modelcontextprotocol/logLevel'] = $logging_level->value;

        parent::__construct($record);
    }
}
