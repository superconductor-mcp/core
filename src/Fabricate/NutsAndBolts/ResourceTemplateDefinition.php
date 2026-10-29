<?php

namespace Fabricate\NutsAndBolts;

use Fabricate\NutsAndBolts\Schema\Annotations;
use Closure;
use Voyager\Contracts\NutsAndBolts\Arrayable;

// a family of resources behind a URI template (RFC 6570, simple {name} variables), e.g. station://sensors/{id}
class ResourceTemplateDefinition implements Arrayable
{
    public function __construct(
        public string $uri_template,
        public string $name,
        public ?string $description = null,
        public ?string $mime_type = null,
        public Closure|string|null $action = null,
        /** @var array<string, Closure|class-string> completers per template variable, for completion/complete */
        public array $completers = [],
        public ?string $title = null,
        public ?Annotations $annotations = null,
        public array $meta = [],
        /** @var list<Icon> 2025-11-25: images a client may show; an array entry is read as {src, mimeType?, sizes?, theme?} */
        public array $icons = [],
    ) {
        Meta::check($meta);
        $this->icons = Icon::list($icons);
    }

    /** @return list<string> the names of the template's {variables}, in order */
    public function variables(): array
    {
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $this->uri_template, $matches);

        return $matches[1];
    }

    public function toArray(): array
    {
        return Meta::attach(array_filter([
            'uriTemplate' => $this->uri_template,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'icons' => Icon::render($this->icons),
            'mimeType' => $this->mime_type,
            'annotations' => $this->annotations?->toArray(),
        ], fn (mixed $value): bool => ! is_null($value)), $this->meta);
    }

    /**
     * The template's variables pulled out of a concrete uri, or null when the uri does not fit the template.
     * A variable matches one path segment: it never spans a "/".
     *
     * @return array<string, string>|null
     */
    public function match(string $uri): ?array
    {
        $pattern = preg_replace_callback(
            '/\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\}/',
            fn (array $variable): string => "(?<{$variable[1]}>[^/]+)",
            preg_quote($this->uri_template, '#'),
        );

        if (preg_match("#^{$pattern}$#", $uri, $matches) !== 1) {
            return null;
        }

        return array_map(rawurldecode(...), array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY));
    }
}
