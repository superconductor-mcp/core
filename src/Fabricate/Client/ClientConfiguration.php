<?php

namespace Fabricate\Client;

use Fabricate\Contracts\Mechanism\Attributes\ClientConfig;
use Fabricate\Mechanism\EntityConfiguration;
use Fabricate\Contracts\Client\ClientConfiguration as ConfigurationContract;
use Fabricate\Contracts\Schema\Messaging\Initialize\InitializeRequest;
use Fabricate\Contracts\Schema\Messaging\ServerRequest;
use Fabricate\Contracts\Schema\ProtocolVersion;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Schema\V20241105;
use Fabricate\Schema\V20250326;
use Fabricate\Schema\V20250618;
use Fabricate\Schema\V20251125;
use JetBrains\PhpStorm\Deprecated;

abstract class ClientConfiguration extends EntityConfiguration implements ConfigurationContract
{
    protected string $accepted_request = ServerRequest::class;

    protected array $capabilities = [
        'tasks', 'extensions', 'elicitation', 'experimental', 'roots', 'sampling',
    ];

    protected array $capability_arguments = [
        'roots' => 'roots',
        'roots.listChanged' => 'announce_when_roots_change',
        'sampling' => 'sampling',
        'sampling.context' => 'support_sampling_context',
        'sampling.tools' => 'support_sampling_tools',
        'elicitation' => 'elicitation',
        'elicitation.form' => 'support_elicitation_forms',
        'elicitation.url' => 'support_elicitation_urls',
        'tasks' => 'tasks',
        'tasks.list' => 'support_tasks_list',
        'tasks.cancel' => 'support_tasks_cancel',
        'tasks.requests' => 'support_tasks_requests',
        'tasks.requests.sampling' => 'support_tasks_sampling_requests',
        'tasks.requests.sampling.createMessage' => 'support_tasks_sampling_create_message_requests',
        'tasks.requests.elicitation' => 'support_tasks_elicitation_requests',
        'tasks.requests.elicitation.create' => 'support_tasks_elicitation_create_requests',
    ];

    protected string $attribute = ClientConfig::class;

    protected bool|array $tasks = false;
    protected bool|array $extensions = false;
    protected bool|array $elicitation = false;
    protected bool|array $experimental = false;
    #[Deprecated] protected bool|array $roots = false;
    #[Deprecated] protected bool|array $sampling = false;

    public function __construct(
        ClientCapabilitiesFactory $capabilities_factory
    ) {
        parent::__construct($capabilities_factory);
    }

    /**
     * Builds the initialize request this client opens a session with, in the version handshakeProtocolVersion() settles on.
     * From 2025-11-25 the request carries its own id; earlier versions leave it to the JSON-RPC envelope.
     */
    public function initializeRequest(RequestId $id, ?string $protocol_version = null): InitializeRequest
    {
        $version = $this->handshakeProtocolVersion($protocol_version);
        $capabilities = $this->capabilities($version);
        $client_info = $this->implementation($version);

        return match (ProtocolVersion::from($version)) {
            ProtocolVersion::V20241105 => new V20241105\Messaging\Initialize\InitializeRequest($version, $capabilities, $client_info),
            ProtocolVersion::V20250326 => new V20250326\Messaging\Initialize\InitializeRequest($version, $capabilities, $client_info),
            ProtocolVersion::V20250618 => new V20250618\Messaging\Initialize\InitializeRequest($version, $capabilities, $client_info),
            ProtocolVersion::V20251125 => new V20251125\Messaging\Initialize\InitializeRequest($id, new V20251125\Messaging\Params\InitializeRequestParams($version, $capabilities, $client_info)),
        };
    }

    // What each capability exposes. A concrete overrides the ones it declares; the rest expose nothing.
    public function tasks(mixed ...$params): array { return []; }
    public function extensions(mixed ...$params): array { return []; }
    public function elicitation(mixed ...$params): array { return []; }
    public function experimental(mixed ...$params): array { return []; }
    public function roots(mixed ...$params): array { return []; }
    public function sampling(mixed ...$params): array { return []; }

    public function tasksConfig(): ?array { return $this->capabilityConfig('tasks'); }
    public function extensionsConfig(): ?array { return $this->capabilityConfig('extensions'); }
    public function elicitationConfig(): ?array { return $this->capabilityConfig('elicitation'); }
    public function experimentalConfig(): ?array { return $this->capabilityConfig('experimental'); }
    public function rootsConfig(): ?array { return $this->capabilityConfig('roots'); }
    public function samplingConfig(): ?array { return $this->capabilityConfig('sampling'); }
}
