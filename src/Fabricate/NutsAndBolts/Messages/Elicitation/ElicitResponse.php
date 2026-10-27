<?php

namespace Fabricate\NutsAndBolts\Messages\Elicitation;

use UnexpectedValueException;
use Fabricate\NutsAndBolts\Messages\MCPResponse;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;

// the client's answer to elicitation/create
final readonly class ElicitResponse extends MCPResponse
{
    public function __construct(
        string|int $id,
        public ElicitAnswer $answer,
    ) {
        parent::__construct($id, array_filter([
            'action' => $answer->action->value,
            'content' => $answer->content,
        ], fn (mixed $value): bool => ! is_null($value)));
    }

    public function toJson(): string
    {
        $result = ['action' => $this->answer->action->value];

        if (! is_null($this->answer->content)) {
            $result['content'] = (object) $this->answer->content;
        }

        return json_encode(['jsonrpc' => '2.0', 'id' => $this->id, 'result' => $result]);
    }

    public static function fromReceived(ReceivedResponse $response): self
    {
        $result = $response->result ?? [];
        $action = ElicitAction::tryFrom((string) ($result['action'] ?? ''));
        $content = $result['content'] ?? null;

        if (is_null($action) || (! is_null($content) && (! is_array($content) || ($content !== [] && array_is_list($content))))) {
            throw new UnexpectedValueException('elicitation result needs an action of accept, decline or cancel, and content as an object when present.');
        }

        // content only means something on accept
        return new self($response->id, new ElicitAnswer($action, $action === ElicitAction::Accept ? ($content ?? []) : null));
    }
}
