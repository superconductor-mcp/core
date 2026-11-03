<?php

namespace Fabricate\Schema\V20260728\Capabilities;

use JetBrains\PhpStorm\Deprecated;
use Fabricate\Schema\V20260728\JSONObject;
use Fabricate\Schema\Common\Concerns\HasCapabilityPayloads;
use Fabricate\Server\ServerCapabilities as BaseCapabilities;
use Fabricate\Contracts\Schema\Contracts\ServerCapabilities as CapabilitiesContract;

class ServerCapabilities extends BaseCapabilities implements CapabilitiesContract
{
    use HasCapabilityPayloads;

    public ?array $tools;
    public ?array $prompts;
    public ?array $resources;
    public ?array $extensions;
    public ?JSONObject $completions;
    public ?array $experimental;
    #[Deprecated] public ?JSONObject $logging;

    public function __construct(
        bool $tools = false,
        bool $resources = false,
        bool $prompts = false,
        bool $completions = false,
        bool $announce_when_tools_change = false,
        bool $announce_individual_resource_updates = false,
        bool $announce_when_resources_change = false,
        bool $announce_when_prompts_change = false,
        ?array $extensions = null,
        ?array $experimental = null,
        #[Deprecated] bool $logging = false,
    ) {
        $this->experimental = $experimental ?? null;

        $this->logging = ($logging) ? new JSONObject() : null;

        $this->completions = ($completions) ? new JSONObject() : null;

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

        $this->extensions = $extensions ?? null;
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

        if(isset($this->completions)) {
            $results['completions'] = $this->payload($this->completions->toArray());
        }

        if(isset($this->logging)) {
            $results['logging'] = $this->payload($this->logging->toArray());
        }

        if(isset($this->extensions)) {
            $results['extensions'] = $this->payload($this->extensions);
        }

        if(isset($this->experimental)) {
            $results['experimental'] = $this->payload($this->experimental);
        }

        return $results;
    }
}
