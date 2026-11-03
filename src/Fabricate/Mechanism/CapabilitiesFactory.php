<?php

namespace Fabricate\Mechanism;

use ReflectionMethod;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Schema\RPCErrorCode;

abstract class CapabilitiesFactory
{
    protected string $capabilities_type;

    public function __construct()
    {
        if(!isset($this->capabilities_type)) {
            throw new SuperconductorMCPException("Invalid capabilities_type - ".get_class($this));
        }
    }

    public function make(string $protocol_version, array $arguments = []): EntityCapabilities
    {
        $ns_version = "V".str_replace('-', '', $protocol_version);
        $class_path = "\\Fabricate\\Schema\\{$ns_version}\\Capabilities\\{$this->capabilities_type}";

        if(!class_exists($class_path)) {
            throw new SuperconductorMCPException("No {$this->capabilities_type} for protocol version {$protocol_version}", RPCErrorCode::INTERNAL_ERROR->value);
        }

        // A version's constructor is the record of what that version has, so anything it doesn't name stays out.
        $declared = array_column((new ReflectionMethod($class_path, '__construct'))->getParameters(), 'name');

        return new $class_path(...array_intersect_key($arguments, array_flip($declared)));
    }
}
