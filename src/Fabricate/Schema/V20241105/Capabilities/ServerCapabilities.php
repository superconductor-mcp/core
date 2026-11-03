<?php

namespace Fabricate\Schema\V20241105\Capabilities;

use Fabricate\Schema\Common\Concerns\HasCapabilityPayloads;
use Fabricate\Server\ServerCapabilities as BaseCapabilities;
use Fabricate\Contracts\Schema\Contracts\ServerCapabilities as CapabilitiesContract;

class ServerCapabilities extends BaseCapabilities implements CapabilitiesContract
{
    use HasCapabilityPayloads;

    public ?array $tools;
    public ?array $resources;
    public ?array $prompts;
    public ?array $logging;
    public ?array $experimental;

    public function __construct(
        bool $tools = false,
        bool $resources = false,
        bool $prompts = false,
        bool $logging = false,
        bool $announce_when_tools_change = false,
        bool $announce_individual_resource_updates = false,
        bool $announce_when_resources_change = false,
        bool $announce_when_prompts_change = false,
        ?array $experimental = null,
    ) {
        $this->experimental = $experimental ?? null;

        $this->logging = $logging ? [] : null;

        if($prompts) {
            $payload = [];

            if($announce_when_prompts_change)
            {
                $payload['listChanged'] = true;
            }

            $this->prompts = $payload;
        }
        else $this->prompts = null;

        if($resources) {
            $payload = [];

            if($announce_individual_resource_updates)
            {
                $payload['subscribe'] = true;
            }

            if($announce_when_resources_change)
            {
                $payload['listChanged'] = true;
            }

            $this->resources = $payload;
        }
        else $this->resources = null;

        if($tools) {
            $payload = [];

            if($announce_when_tools_change)
            {
                $payload['listChanged'] = true;
            }

            $this->tools = $payload;
        }
        else $this->tools = null;
    }

    public function toArray(): array
    {
        $results = [];

        if(!is_null($this->tools)) {
            $results['tools'] = $this->payload($this->tools);
        }

        if(!is_null($this->prompts)) {
            $results['prompts'] = $this->payload($this->prompts);
        }

        if(!is_null($this->resources)) {
            $results['resources'] = $this->payload($this->resources);
        }

        if(!is_null($this->logging)) {
            $results['logging'] = $this->payload($this->logging);
        }

        if(isset($this->experimental)) {
            $results['experimental'] = $this->payload($this->experimental);
        }

        return $results;
    }
}
