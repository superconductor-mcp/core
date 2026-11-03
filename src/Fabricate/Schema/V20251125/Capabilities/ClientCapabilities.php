<?php

namespace Fabricate\Schema\V20251125\Capabilities;

use Fabricate\Schema\Common\Concerns\HasCapabilityPayloads;
use Fabricate\Client\ClientCapabilities as BaseCapabilities;
use Fabricate\Contracts\Schema\Contracts\ClientCapabilities as CapabilitiesContract;

class ClientCapabilities extends BaseCapabilities implements CapabilitiesContract
{
    use HasCapabilityPayloads;

    public ?array $roots;
    public ?array $tasks;
    public ?array $sampling;
    public ?array $elicitation;
    public ?array $experimental;

    public function __construct(
        bool   $sampling = false,
        bool   $roots = false,
        bool   $elicitation = false,
        bool   $tasks = false,
        bool   $announce_when_roots_change = false,
        bool   $support_sampling_context = false,
        bool   $support_sampling_tools = false,
        bool   $support_elicitation_forms = false,
        bool   $support_elicitation_urls = false,
        bool   $support_tasks_list = false,
        bool   $support_tasks_cancel = false,
        bool   $support_tasks_requests = false,
        bool   $support_tasks_sampling_requests = false,
        bool   $support_tasks_sampling_create_message_requests = false,
        bool   $support_tasks_elicitation_requests = false,
        bool   $support_tasks_elicitation_create_requests = false,
        ?array $experimental = null,
    ) {
        $this->experimental = $experimental ?? null;

        if($roots) {
            $payload = [];

            if($announce_when_roots_change)
            {
                $payload['listChanged'] = true;
            }

            $this->roots = $payload;
        }
        else $this->roots = null;

        if($sampling) {
            $payload = [];

            if($support_sampling_context)
            {
                $payload['context'] = [];
            }

            if($support_sampling_tools)
            {
                $payload['tools'] = [];
            }

            $this->sampling = $payload;
        }
        else $this->sampling = null;

        if($elicitation) {
            $payload = [];

            if($support_elicitation_forms)
            {
                $payload['form'] = [];
            }

            if($support_elicitation_urls)
            {
                $payload['url'] = [];
            }

            $this->elicitation = $payload;
        }
        else $this->elicitation = null;

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

                if($support_tasks_sampling_requests)
                {
                    $payload['requests']['sampling'] = [];

                    if($support_tasks_sampling_create_message_requests)
                    {
                        $payload['requests']['sampling']['createMessage'] = [];
                    }
                }

                if($support_tasks_elicitation_requests)
                {
                    $payload['requests']['elicitation'] = [];

                    if($support_tasks_elicitation_create_requests)
                    {
                        $payload['requests']['elicitation']['create'] = [];
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

        if(!is_null($this->sampling)) {
            $results['sampling'] = $this->payload($this->sampling);
        }

        if(!is_null($this->roots)) {
            $results['roots'] = $this->payload($this->roots);
        }

        if(!is_null($this->elicitation)) {
            $results['elicitation'] = $this->payload($this->elicitation);
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
