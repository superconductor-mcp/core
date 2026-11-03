<?php

namespace Fabricate\Schema\V20260728\Capabilities;

use JetBrains\PhpStorm\Deprecated;
use Fabricate\Schema\Common\Concerns\HasCapabilityPayloads;
use Fabricate\Client\ClientCapabilities as BaseCapabilities;
use Fabricate\Contracts\Schema\Contracts\ClientCapabilities as CapabilitiesContract;

class ClientCapabilities extends BaseCapabilities implements CapabilitiesContract
{
    use HasCapabilityPayloads;

    public ?array $extensions;
    public ?array $elicitation;
    public ?array $experimental;
    #[Deprecated] public ?array $roots;
    #[Deprecated] public ?array $sampling;

    public function __construct(
        bool   $elicitation = false,
        ?array $extensions = null,
        bool   $support_elicitation_forms = false,
        bool   $support_elicitation_urls = false,
        ?array $experimental = null,
        #[Deprecated] bool   $sampling = false,
        #[Deprecated] bool   $roots = false,
        #[Deprecated] bool   $support_sampling_context = false,
        #[Deprecated] bool   $support_sampling_tools = false,
    ) {
        $this->experimental = $experimental ?? null;

        $this->roots = $roots ? [] : null;

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

        $this->extensions = $extensions ?? null;
    }


    public function toArray(): array
    {
        $results = [];

        if(!is_null($this->elicitation)) {
            $results['elicitation'] = $this->payload($this->elicitation);
        }

        if(!is_null($this->roots)) {
            $results['roots'] = $this->payload($this->roots);
        }

        if(!is_null($this->sampling)) {
            $results['sampling'] = $this->payload($this->sampling);
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
