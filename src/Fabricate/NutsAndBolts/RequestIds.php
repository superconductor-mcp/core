<?php

namespace Fabricate\NutsAndBolts;

use LogicException;

final class RequestIds
{
    /** @var array<string, true> */
    private array $used = [];

    private ?int $high = null;

    // a request from the client: true the first time its id is seen, false when the id is already in the log
    public function receive(string|int $id): bool
    {
        return $this->record($id);
    }

    // a request from the server: the next integer after the greatest one in the log, or 0
    public function next(): int
    {
        $id = is_null($this->high) ? 0 : $this->high + 1;

        if (! $this->record($id)) {
            throw new LogicException("Request id [{$id}] is already in the log.");
        }

        return $id;
    }

    public function forget(): void
    {
        $this->used = [];
        $this->high = null;
    }

    private function record(string|int $id): bool
    {
        $key = (is_int($id) ? 'i:' : 's:').$id;

        if (isset($this->used[$key])) {
            return false;
        }

        if (is_int($id)) {
            $this->high = max($id, $this->high ?? $id);
        }

        return $this->used[$key] = true;
    }
}
