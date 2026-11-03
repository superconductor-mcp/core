<?php

namespace App\Mcp\Clients;

use App\Mcp\Elicitation\CuriousElicitation;
use App\Mcp\Roots\CuriousRoots;
use App\Mcp\Sampling\CuriousSampling;
use Fabricate\Client\ClientConfiguration;
use Fabricate\Contracts\Mechanism\Attributes\ClientConfig;

#[ClientConfig('curious-client', 'Asks servers questions.', '0.11.0')]
class CuriousClient extends ClientConfiguration
{
    protected array $supported_protocol_versions = [
        '2024-11-05', '2025-03-26','2025-06-18','2025-11-25','2026-07-28'
    ];

    protected bool|array $roots = ['listChanged' => true];

    protected bool|array $sampling = true;

    protected bool|array $elicitation = ['form' => true];

    protected bool|array $tasks = [
        'list' => true,
        'cancel' => true,
        'requests' => ['sampling' => ['createMessage' => true], 'elicitation' => ['create' => true]],
    ];

    public function roots(): ?string
    {
        return CuriousRoots::class;
    }

    public function sampling(): ?string
    {
        return CuriousSampling::class;
    }

    public function elicitation(): ?string
    {
        return CuriousElicitation::class;
    }
}
