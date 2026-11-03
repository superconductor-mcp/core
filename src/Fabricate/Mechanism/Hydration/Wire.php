<?php

namespace Fabricate\Mechanism\Hydration;

use BackedEnum;
use Fabricate\Contracts\Schema\Cursor;
use Fabricate\Contracts\Schema\ProgressToken;
use Fabricate\Contracts\Schema\RequestId;
use Fabricate\Contracts\Schema\RPCErrorCode;

/**
 * Typed reads of one decoded JSON object. Every reader throws a HydrationException naming the member
 * that is missing or of the wrong type, so a hydrator can build a class field by field.
 */
final readonly class Wire
{
    /**
     * @param array<string, mixed> $data The object being read.
     * @param string $path Where the object sits in the message, for error messages.
     */
    public function __construct(
        public array $data,
        public string $path = 'params',
    ) {}

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function string(string $key): string
    {
        $value = $this->data[$key] ?? null;
        if(!is_string($value)) throw $this->invalid($key, 'a string');

        return $value;
    }

    public function optionalString(string $key): ?string
    {
        return $this->has($key) ? $this->string($key) : null;
    }

    public function int(string $key): int
    {
        $value = $this->data[$key] ?? null;
        if(!is_int($value)) throw $this->invalid($key, 'an integer');

        return $value;
    }

    public function optionalInt(string $key): ?int
    {
        return $this->has($key) ? $this->int($key) : null;
    }

    public function number(string $key): int|float
    {
        $value = $this->data[$key] ?? null;
        if(!is_int($value) && !is_float($value)) throw $this->invalid($key, 'a number');

        return $value;
    }

    public function optionalNumber(string $key): int|float|null
    {
        return $this->has($key) ? $this->number($key) : null;
    }

    public function bool(string $key): bool
    {
        $value = $this->data[$key] ?? null;
        if(!is_bool($value)) throw $this->invalid($key, 'a boolean');

        return $value;
    }

    public function optionalBool(string $key): ?bool
    {
        return $this->has($key) ? $this->bool($key) : null;
    }

    /**
     * A JSON object member, read as the associative array json_decode() produced.
     *
     * @return array<string, mixed>
     */
    public function object(string $key): array
    {
        $value = $this->data[$key] ?? null;
        if(!is_array($value) || ($value !== [] && array_is_list($value))) throw $this->invalid($key, 'an object');

        return $value;
    }

    /** @return array<string, mixed>|null */
    public function optionalObject(string $key): ?array
    {
        return $this->has($key) ? $this->object($key) : null;
    }

    /**
     * A JSON array member.
     *
     * @return list<mixed>
     */
    public function list(string $key): array
    {
        $value = $this->data[$key] ?? null;
        if(!is_array($value) || !array_is_list($value)) throw $this->invalid($key, 'an array');

        return $value;
    }

    /** @return list<mixed>|null */
    public function optionalList(string $key): ?array
    {
        return $this->has($key) ? $this->list($key) : null;
    }

    /** A nested object member as its own reader. */
    public function child(string $key): Wire
    {
        return new Wire($this->object($key), "{$this->path}.{$key}");
    }

    public function optionalChild(string $key): ?Wire
    {
        return $this->has($key) ? $this->child($key) : null;
    }

    /**
     * Each element of an array member as its own reader.
     *
     * @return list<Wire>
     */
    public function children(string $key): array
    {
        $readers = [];
        foreach($this->list($key) as $index => $item)
        {
            if(!is_array($item)) throw new HydrationException("{$this->path}.{$key}[{$index}] must be an object.");
            $readers[] = new Wire($item, "{$this->path}.{$key}[{$index}]");
        }

        return $readers;
    }

    /** @return list<Wire>|null */
    public function optionalChildren(string $key): ?array
    {
        return $this->has($key) ? $this->children($key) : null;
    }

    /**
     * Each member of an object member as its own reader, keyed as on the wire.
     *
     * @return array<string, Wire>
     */
    public function keyedChildren(string $key): array
    {
        $readers = [];
        foreach($this->object($key) as $name => $item)
        {
            if(!is_array($item)) throw new HydrationException("{$this->path}.{$key}.{$name} must be an object.");
            $readers[$name] = new Wire($item, "{$this->path}.{$key}.{$name}");
        }

        return $readers;
    }

    /** @return array<string, Wire>|null */
    public function optionalKeyedChildren(string $key): ?array
    {
        return $this->has($key) ? $this->keyedChildren($key) : null;
    }

    /**
     * @template T of BackedEnum
     * @param class-string<T> $enum
     * @return T
     */
    public function enum(string $key, string $enum): BackedEnum
    {
        $value = $this->data[$key] ?? null;
        $case = is_string($value) || is_int($value) ? $enum::tryFrom($value) : null;
        if(is_null($case)) throw $this->invalid($key, 'one of '.implode(', ', array_map(fn(BackedEnum $c) => json_encode($c->value), $enum::cases())));

        return $case;
    }

    /**
     * @template T of BackedEnum
     * @param class-string<T> $enum
     * @return T|null
     */
    public function optionalEnum(string $key, string $enum): ?BackedEnum
    {
        return $this->has($key) ? $this->enum($key, $enum) : null;
    }

    public function requestId(string $key): RequestId
    {
        $value = $this->data[$key] ?? null;
        if(!is_string($value) && !is_int($value)) throw $this->invalid($key, 'a string or integer id');

        return new RequestId($value);
    }

    public function optionalRequestId(string $key): ?RequestId
    {
        return $this->has($key) ? $this->requestId($key) : null;
    }

    public function optionalCursor(string $key): ?Cursor
    {
        return $this->has($key) ? new Cursor($this->string($key)) : null;
    }

    /** The progress token inside this object's _meta, when there is one. */
    public function progressToken(): ?ProgressToken
    {
        $token = $this->optionalObject('_meta')['progressToken'] ?? null;
        if(is_null($token)) return null;
        if(!is_string($token) && !is_int($token)) throw new HydrationException("{$this->path}._meta.progressToken must be a string or integer.");

        return new ProgressToken($token);
    }

    /**
     * This object's _meta without the progress token, which the request classes carry separately.
     *
     * @return array<string, mixed>|null
     */
    public function meta(): ?array
    {
        $meta = $this->optionalObject('_meta');
        if(is_null($meta)) return null;

        unset($meta['progressToken']);

        return $meta;
    }

    public function invalid(string $key, string $expected): HydrationException
    {
        $state = $this->has($key) ? 'must be' : 'is required and must be';

        return new HydrationException("{$this->path}.{$key} {$state} {$expected}.", RPCErrorCode::INVALID_PARAMS);
    }
}
