<?php

namespace Fabricate\Schema\V20241105\Capabilities;

use Fabricate\Schema\Common\Concerns\HasCapabilityPayloads;
use Fabricate\Client\ClientCapabilities as BaseCapabilities;
use Fabricate\Contracts\Schema\Contracts\ClientCapabilities as CapabilitiesContract;

class ClientCapabilities extends BaseCapabilities implements CapabilitiesContract
{
    use HasCapabilityPayloads;

    public ?array $experimental;
    public ?array $roots;
    public ?array $sampling;

    public function __construct(
        bool   $sampling = false,
        bool   $roots = false,
        bool   $announce_when_roots_change = false,
        ?array $experimental = null,
    ) {
        $this->sampling = $sampling ? [] : null;

        if($roots) {
            $payload = [];

            if($announce_when_roots_change)
            {
                $payload['listChanged'] = true;
            }

            $this->roots = $payload;
        }
        else $this->roots = null;

        $this->experimental = $experimental ?? null;
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

        if(isset($this->experimental)) {
            $results['experimental'] = $this->payload($this->experimental);
        }

        return $results;
    }
}
