<?php

declare(strict_types=1);

namespace Fabricate\NutsAndBolts\Messages\Errors;

// 2025-11-25: the request can't go on until the user finishes these URL elicitations; the client may retry after
final readonly class UrlElicitationRequiredError extends JsonRpcError
{
    public const int CODE = -32042;

    /** @param list<array{mode: 'url', message: string, elicitationId: string, url: string}> $elicitations */
    public function __construct(string|int|null $id, array $elicitations, string $message = 'This request requires more information.')
    {
        parent::__construct($id, self::CODE, $message, ['elicitations' => $elicitations]);
    }
}
