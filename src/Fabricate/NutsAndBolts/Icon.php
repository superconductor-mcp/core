<?php

namespace Fabricate\NutsAndBolts;

use InvalidArgumentException;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// 2025-11-25: an image a client may show beside a tool, resource, resource template, prompt or server; sent as declared, ungated
class Icon implements Arrayable
{
    public const array THEMES = ['light', 'dark'];

    /** @param list<string>|null $sizes WxH strings such as "48x48", or "any" for a scalable icon */
    public function __construct(
        public string $src,
        public ?string $mime_type = null,
        public ?array $sizes = null,
        public ?string $theme = null,
    ) {
        if ($src === '') {
            throw new InvalidArgumentException('an icon needs a non-empty src.');
        }

        if (! is_null($sizes) && (! array_is_list($sizes) || array_filter($sizes, 'is_string') !== $sizes)) {
            throw new InvalidArgumentException('an icon\'s sizes must be a list of strings.');
        }

        if (! is_null($theme) && ! in_array($theme, self::THEMES, true)) {
            throw new InvalidArgumentException('an icon\'s theme must be light or dark.');
        }
    }

    /**
     * Every entry checked: an Icon stays, a spec-shaped array ({src, mimeType?, sizes?, theme?}) is read.
     *
     * @param array<int, Icon|array> $icons
     * @return list<Icon>
     * @throws InvalidArgumentException naming the entry that is wrong
     */
    public static function list(array $icons): array
    {
        if (! array_is_list($icons)) {
            throw new InvalidArgumentException('icons must be a list.');
        }

        return array_map(function (mixed $icon, int $index): self {
            if ($icon instanceof self) {
                return $icon;
            }

            if (! is_array($icon) || ! is_string($icon['src'] ?? null) || ! is_string($icon['mimeType'] ?? '') || ! is_array($icon['sizes'] ?? []) || ! is_string($icon['theme'] ?? '')) {
                throw new InvalidArgumentException("icon {$index} must be an Icon or an array with a string src and string mimeType, theme and list sizes.");
            }

            try {
                return new self($icon['src'], $icon['mimeType'] ?? null, $icon['sizes'] ?? null, $icon['theme'] ?? null);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("icon {$index}: {$e->getMessage()}");
            }
        }, $icons, array_keys($icons));
    }

    /**
     * The `icons` key of a definition: nothing for no icons.
     *
     * @param list<Icon> $icons
     */
    public static function render(array $icons): ?array
    {
        return $icons === [] ? null : array_map(fn (self $icon): array => $icon->toArray(), $icons);
    }

    public function toArray(): array
    {
        return array_filter([
            'src' => $this->src,
            'mimeType' => $this->mime_type,
            'sizes' => $this->sizes,
            'theme' => $this->theme,
        ], fn (mixed $value): bool => ! is_null($value));
    }
}
