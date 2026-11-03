<?php

namespace Fabricate\Schema\V20251125\Capabilities;

use Fabricate\Schema\Common\Concerns\HasCapabilityPayloads;
use Fabricate\Server\ServerCapabilities as BaseCapabilities;
use Fabricate\Contracts\Schema\Contracts\ServerCapabilities as CapabilitiesContract;

class ServerCapabilities extends BaseCapabilities implements CapabilitiesContract
{
    use HasCapabilityPayloads;

    public ?array $tools;
    public ?array $tasks;
    public ?array $prompts;
    public ?array $logging;
    public ?array $resources;
    public ?array $completions;
    public ?array $experimental;

    public function __construct(
        bool $tools = false,
        bool $logging = false,
        bool $resources = false,
        bool $prompts = false,
        bool $completions = false,
        bool $tasks = false,
        bool $announce_when_tools_change = false,
        bool $announce_individual_resource_updates = false,
        bool $announce_when_resources_change = false,
        bool $announce_when_prompts_change = false,
        bool   $support_tasks_list = false,
        bool   $support_tasks_cancel = false,
        bool   $support_tasks_requests = false,
        bool   $support_tasks_tool_requests = false,
        bool   $support_tasks_tool_call_requests = false,
        ?array $experimental = null,
    ) {
        $this->experimental = $experimental ?? null;

        $this->logging = $logging ? [] : null;

        $this->completions = $completions ? [] : null;

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

        if($tasks) {
            $payload = [];

            if($support_tasks_list)
            {
                $payload['list'] = [];
            }

            if($support_tasks_cancel)
            {
                $payload['cancel'] = [];
            }

            if($support_tasks_requests)
            {
                $payload['requests'] = [];

                if($support_tasks_tool_requests)
                {
                    $payload['requests']['tools'] = [];

                    if($support_tasks_tool_call_requests)
                    {
                        $payload['requests']['tools']['call'] = [];
                    }
                }
            }

            $this->tasks = $payload;
        }
        else $this->tasks = null;
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

        if(!is_null($this->completions)) {
            $results['completions'] = $this->payload($this->completions);
        }

        if(!is_null($this->tasks)) {
            $results['tasks'] = $this->payload($this->tasks);
        }

        if(isset($this->experimental)) {
            $results['experimental'] = $this->payload($this->experimental);
        }

        return $results;
    }
}
