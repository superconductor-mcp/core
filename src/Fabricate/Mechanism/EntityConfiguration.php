<?php

namespace Fabricate\Mechanism;

use Fabricate\Contracts\Mechanism\Attributes\Icon as IconAttribute;
use Fabricate\Schema\Common\Concerns\Icon;
use Fabricate\Contracts\Core\SuperconductorMCPException;
use Fabricate\Contracts\Mechanism\EntityConfiguration as ConfigurationContract;
use Fabricate\Contracts\Mechanism\MCPMechanismException;
use Fabricate\Contracts\Schema\Contracts\Implementation;
use Fabricate\Contracts\Schema\Messaging\Request;
use Fabricate\Contracts\Schema\ProtocolVersion;
use Fabricate\Contracts\Schema\RPCErrorCode;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use Fabricate\Schema\V20260728;
use Fabricate\Schema\V20260728\Concerns\MCPErrorCode;
use ReflectionClass;
use Voyager\NutsAndBolts\Str;

abstract class EntityConfiguration implements ConfigurationContract
{
    /** The marker on requests this entity receives: the ones its peer is allowed to send. */
    protected string $accepted_request = '';

    /** Every capability this entity can declare. Each has a {name}Config() method. */
    protected array $capabilities = [];

    /**
     * Wire path => the argument that switches it on in a capabilities constructor.
     * A capability with no entry here has open keys, and its config passes through whole.
     */
    protected array $capability_arguments = [];

    /** @var class-string The attribute a concrete carries its name, description and version in. */
    protected string $attribute;

    protected array $supported_protocol_versions = ['2026-07-28'];

    public function __construct(
        protected CapabilitiesFactory $capabilities_factory
    ) {}

    /**
     * The name this entity is registered and announced under: the attribute's, or the kebab-case class name.
     */
    public function name(): string
    {
        return $this->attributeInstance()?->name ?? Str::kebab(class_basename(static::class));
    }

    /**
     * Builds the Implementation object this entity announces itself with in the given version.
     */
    /**
     * Whether the attribute names a version, which implementation() needs to describe this entity.
     */
    public function identifiesItself(): bool
    {
        return ! is_null($this->attributeInstance()?->version);
    }

    public function implementation(string $protocol_version): Implementation
    {
        $attribute = $this->attributeInstance();

        if (is_null($attribute?->version)) {
            throw new MCPMechanismException(static::class.' needs a version in its #['.class_basename($this->attribute).'] attribute to announce itself.');
        }

        return match (ProtocolVersion::from($protocol_version)) {
            ProtocolVersion::V20241105 => new V20241105\Implementation($attribute->name, $attribute->version),
            ProtocolVersion::V20250326 => new V20250326\Implementation($attribute->name, $attribute->version),
            ProtocolVersion::V20250618 => new V20250618\Implementation($attribute->name, $attribute->version, $attribute->title),
            ProtocolVersion::V20251125 => new V20251125\Implementation($attribute->name, $attribute->version, $this->icons(), $attribute->description, $attribute->websiteUrl, $attribute->title),
            ProtocolVersion::V20260728 => new V20260728\Implementation($attribute->name, $attribute->version, null, $attribute->description, $attribute->websiteUrl, $attribute->title),
        };
    }

    /**
     * The configuration class's #[Icon]s as the schema's icons, or null when it has none.
     *
     * @return list<Icon>|null
     */
    protected function icons(): ?array
    {
        return array_map(function($attribute) {
            $icon = $attribute->newInstance();

            return new Icon($icon->src, $icon->mimeType, $icon->sizes, $icon->theme);
        }, new ReflectionClass(static::class)->getAttributes(IconAttribute::class)) ?: null;
    }

    /**
     * The version to answer a peer in: theirs when this entity supports it, otherwise the newest it does.
     */
    public function matchProtocolVersion(string $peer_protocol_version): string
    {
        $supported = $this->supportedProtocolVersions();

        return in_array($peer_protocol_version, $supported) ? $peer_protocol_version : end($supported);
    }

    /**
     * The version to open a session in: the requested one when this entity and the app both support it,
     * otherwise the newest they do. Only versions with an initialize handshake qualify.
     */
    public function handshakeProtocolVersion(?string $requested_protocol_version = null): string
    {
        $versions = $this->handshakeProtocolVersions();

        if ($versions === []) {
            throw new MCPMechanismException(static::class.' supports no protocol version with an initialize handshake.');
        }

        return in_array($requested_protocol_version, $versions, true) ? $requested_protocol_version : max($versions);
    }

    /**
     * The supported versions that open a session with initialize: all of them less 2026-07-28, which has no handshake.
     *
     * @return list<string>
     */
    public function handshakeProtocolVersions(): array
    {
        return array_values(array_diff($this->supportedProtocolVersions(), [ProtocolVersion::V20260728->value]));
    }

    protected function attributeInstance(): ?object
    {
        $attributes = new ReflectionClass(static::class)->getAttributes($this->attribute);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }

    public function capabilities(string $protocol_version): EntityCapabilities
    {
        $local_support = in_array($protocol_version, $this->supported_protocol_versions);
        $global_support = $this->protocolSupportedInConfig($protocol_version);
        if($local_support && $global_support)
        {
            return $this->capabilities_factory->make($protocol_version, $this->capabilityArgumentsFor($protocol_version));
        }

        // Versions with a handshake refuse in initialize's terms; a stateless request has its own error code.
        $negotiated = [ProtocolVersion::V20241105, ProtocolVersion::V20250326, ProtocolVersion::V20250618, ProtocolVersion::V20251125];
        $code = in_array(ProtocolVersion::tryFrom($protocol_version), $negotiated, true)
            ? RPCErrorCode::INVALID_PARAMS->value
            : MCPErrorCode::UNSUPPORTED_PROTOCOL_VERSION->value;

        throw new SuperconductorMCPException("Incompatible protocol version  provided: {$protocol_version}", $code);
    }

    public function supportedProtocolVersions(): array
    {
        return array_values(array_filter($this->supported_protocol_versions, $this->protocolSupportedInConfig(...)));
    }

    public function acceptsRequest(Request $request): bool
    {
        return is_a($request, $this->accepted_request);
    }

    public function protocolSupportedInConfig(string $protocol_version) : bool
    {
        $config = config('mcp.supported_protocol_versions', []);

        return in_array($protocol_version, $config);
    }

    protected function capabilityConfig(string $name): ?array
    {
        return match($this->$name) {
            false => null,
            true => [],
            default => $this->$name,
        };
    }

    /**
     * The capability arguments for one protocol version; the same in every version unless a configuration says otherwise.
     */
    protected function capabilityArgumentsFor(string $protocol_version): array
    {
        return $this->capabilityArguments();
    }

    private function capabilityArguments(): array
    {
        $arguments = [];

        foreach($this->capabilities as $name)
        {
            $config = $this->{"{$name}Config"}();

            if(is_null($config)) continue;

            $arguments += isset($this->capability_arguments[$name])
                ? $this->switchedOnArguments($name, $config)
                : [$name => $config];
        }

        return $arguments;
    }

    private function switchedOnArguments(string $path, array $config): array
    {
        $arguments = [$this->capability_arguments[$path] => true];

        foreach($config as $key => $value)
        {
            $child = "{$path}.{$key}";

            if(!isset($this->capability_arguments[$child])) {
                throw new SuperconductorMCPException("Unknown capability key {$child} in ".static::class, RPCErrorCode::INTERNAL_ERROR->value);
            }

            if($value === false) continue;

            $arguments += $this->switchedOnArguments($child, $value === true ? [] : $value);
        }

        return $arguments;
    }
}
