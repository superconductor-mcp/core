<?php

use Fabricate\NutsAndBolts\Icon;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAnswer;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitResponse;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Schema\ArrayProperty;
use Fabricate\NutsAndBolts\Schema\EnumProperty;
use Fabricate\NutsAndBolts\Schema\IntegerProperty;
use Fabricate\NutsAndBolts\Schema\NumberProperty;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Schema\StringProperty;
use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\PromptDefinition;
use Fabricate\NutsAndBolts\ResourceDefinition;
use Fabricate\NutsAndBolts\ResourceTemplateDefinition;
use Fabricate\Server\MCPServer;

// icons (SEP-973)

it('sends icons on all four definitions as declared, and leaves the key out with none', function () {
    $icon = new Icon('https://example.com/a.png', 'image/png', ['48x48', 'any'], 'dark');
    $wire = ['src' => 'https://example.com/a.png', 'mimeType' => 'image/png', 'sizes' => ['48x48', 'any'], 'theme' => 'dark'];

    foreach ([
        new ToolDefinition('t', icons: [$icon]),
        new ResourceDefinition('file:///a', 'a', icons: [$wire]),
        new ResourceTemplateDefinition('file:///{id}', 'a', icons: [$icon]),
        new PromptDefinition('p', icons: [$wire]),
    ] as $definition) {
        expect($definition->toArray()['icons'])->toBe([$wire]);
    }

    expect(new Icon('data:image/png;base64,AAAA'))->toArray()->toBe(['src' => 'data:image/png;base64,AAAA'])
        ->and(new ToolDefinition('t'))->toArray()->not->toHaveKey('icons');
});

it('refuses a bad icon when the definition is built', function (array $icons, string $message) {
    expect(fn () => new ToolDefinition('t', icons: $icons))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'empty src' => [[['src' => '']], 'icon 0: an icon needs a non-empty src'],
    'no src' => [[['mimeType' => 'image/png']], 'icon 0 must be'],
    'theme' => [[['src' => 'x:y'], ['src' => 'x:y', 'theme' => 'blue']], 'icon 1: an icon\'s theme must be light or dark'],
    'sizes' => [[['src' => 'x:y', 'sizes' => '48x48']], 'icon 0 must be'],
    'size item' => [[['src' => 'x:y', 'sizes' => [48]]], 'sizes must be a list of strings'],
    'not a list' => [['a' => ['src' => 'x:y']], 'icons must be a list'],
]);

it('carries icons, description and websiteUrl in the server identity, checked', function () {
    $server = new class extends MCPServer {
        protected array $entity_info = ['name' => 's', 'version' => '1', 'description' => 'd', 'websiteUrl' => 'https://example.com', 'icons' => [['src' => 'https://example.com/i.svg', 'mimeType' => 'image/svg+xml']]];
    };

    expect($server->serverInfo())->toBe(['name' => 's', 'version' => '1', 'description' => 'd', 'websiteUrl' => 'https://example.com', 'icons' => [['src' => 'https://example.com/i.svg', 'mimeType' => 'image/svg+xml']]]);

    foreach ([['icons' => [['src' => '']]], ['icons' => [['src' => 'x:y', 'theme' => 'x']]], ['description' => 1], ['websiteUrl' => []]] as $bad) {
        $broken = new class($bad) extends MCPServer {
            public function __construct(array $bad) { $this->entity_info = ['name' => 's', 'version' => '1'] + $bad; }
        };
        expect(fn () => $broken->serverInfo())->toThrow(InvalidArgumentException::class);
    }
});

// tool names (SEP-986)

it('accepts a tool name of letters, digits, underscore, dash and dot up to 128 characters', function (string $name) {
    expect((new ToolDefinition($name))->name)->toBe($name);
})->with(['get_weather', 'DATA_EXPORT_v2', 'admin.tools.list', 'a-b', 'x', str_repeat('a', 128)]);

it('refuses any other tool name, saying why', function (string $name) {
    expect(fn () => new ToolDefinition($name))->toThrow(InvalidArgumentException::class, 'must be 1 to 128 characters of A-Z, a-z, 0-9, underscore, dash and dot');
})->with(['', 'two words', 'a,b', 'tool/one', 'é', "line\nbreak", "trailing\n", str_repeat('a', 129)]);

// elicitation forms (SEP-1330, SEP-1034)

function formOf(array $properties, array $required = []): array
{
    return ['type' => 'object', 'properties' => $properties] + ($required === [] ? [] : ['required' => $required]);
}

function refusal(array $schema): string
{
    try {
        ObjectSchema::fromArray($schema);
    } catch (InvalidArgumentException $e) {
        return $e->getMessage();
    }

    return '';
}

it('reads every enum form and writes it back as it came', function () {
    $properties = [
        'plain' => ['type' => 'string', 'title' => 'Plain', 'enum' => ['a', 'b'], 'default' => 'a'],
        'titled' => ['type' => 'string', 'oneOf' => [['const' => 'a', 'title' => 'Alpha'], ['const' => 'b', 'title' => 'Beta']], 'default' => 'b'],
        'legacy' => ['type' => 'string', 'enum' => ['a', 'b'], 'enumNames' => ['Alpha', 'Beta'], 'default' => 'a'],
        'many' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 2, 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c']], 'default' => ['a']],
        'titledMany' => ['type' => 'array', 'items' => ['anyOf' => [['const' => 'a', 'title' => 'Alpha'], ['const' => 'b', 'title' => 'Beta']]], 'default' => ['a', 'b']],
    ];

    $schema = ObjectSchema::fromArray(formOf($properties));

    expect($schema->isFlat())->toBeTrue()
        ->and(json_encode($schema->toArray()['properties']))->toBe(json_encode($properties));
});

it('builds the same forms from the primitives', function () {
    expect((new EnumProperty(['a', 'b'], ['Alpha', 'Beta'], titled: EnumProperty::ONE_OF, default: 'a'))->toArray())
        ->toBe(['type' => 'string', 'oneOf' => [['const' => 'a', 'title' => 'Alpha'], ['const' => 'b', 'title' => 'Beta']], 'default' => 'a'])
        ->and((new ArrayProperty(new EnumProperty(['a', 'b'], ['Alpha', 'Beta'], titled: EnumProperty::ANY_OF), min_items: 1))->toArray())
        ->toBe(['type' => 'array', 'minItems' => 1, 'items' => ['anyOf' => [['const' => 'a', 'title' => 'Alpha'], ['const' => 'b', 'title' => 'Beta']]]]);
});

it('refuses a malformed enum or multi-select form', function (array $property, string $message) {
    expect(refusal(formOf(['x' => $property])))->toContain($message);
})->with([
    'oneOf with enum' => [['type' => 'string', 'enum' => ['a'], 'oneOf' => [['const' => 'a', 'title' => 'A']]], 'excludes enum'],
    'oneOf item shape' => [['type' => 'string', 'oneOf' => [['const' => 'a']]], 'oneOf must be a list of {const, title}'],
    'oneOf empty' => [['type' => 'string', 'oneOf' => []], 'oneOf must be a list'],
    'anyOf item shape' => [['type' => 'array', 'items' => ['anyOf' => [['const' => 1, 'title' => 'A']]]], 'anyOf must be a list'],
    'items not strings' => [['type' => 'array', 'items' => ['type' => 'integer']], 'items must be'],
    'items extra keyword' => [['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a'], 'title' => 'T']], 'items must be'],
    'items missing' => [['type' => 'array'], 'items must be'],
    'array keyword' => [['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a']], 'uniqueItems' => true], 'uniqueItems'],
    'min above max' => [['type' => 'array', 'minItems' => 3, 'maxItems' => 1, 'items' => ['type' => 'string', 'enum' => ['a']]], 'min_items must not be above max_items'],
    'minItems type' => [['type' => 'array', 'minItems' => 'one', 'items' => ['type' => 'string', 'enum' => ['a']]], 'wrong type'],
]);

it('refuses a default that breaks the field\'s own constraints', function (array $property, string $message) {
    expect(refusal(formOf(['x' => $property])))->toContain('[x] default '.$message);
})->with([
    'string too short' => [['type' => 'string', 'minLength' => 3, 'default' => 'ab'], 'must be at least 3 characters'],
    'string format' => [['type' => 'string', 'format' => 'email', 'default' => 'nope'], 'must be an email'],
    'number above max' => [['type' => 'number', 'maximum' => 5, 'default' => 6], 'must be at most 5'],
    'integer fraction' => [['type' => 'integer', 'default' => 2.5], 'must be an integer'],
    'enum outside' => [['type' => 'string', 'enum' => ['a', 'b'], 'default' => 'c'], 'must be one of: a, b'],
    'oneOf outside' => [['type' => 'string', 'oneOf' => [['const' => 'a', 'title' => 'A']], 'default' => 'b'], 'must be one of: a'],
    'multi outside' => [['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a']], 'default' => ['a', 'z']], 'item 1 must be one of: a'],
    'multi too many' => [['type' => 'array', 'maxItems' => 1, 'items' => ['type' => 'string', 'enum' => ['a', 'b']], 'default' => ['a', 'b']], 'must have at most 1 items'],
    'multi too few' => [['type' => 'array', 'minItems' => 2, 'items' => ['type' => 'string', 'enum' => ['a', 'b']], 'default' => ['a']], 'must have at least 2 items'],
]);

it('accepts a default that fits, on every kind of field', function () {
    $schema = ObjectSchema::fromArray(formOf([
        's' => ['type' => 'string', 'minLength' => 2, 'default' => 'ok'],
        'n' => ['type' => 'number', 'minimum' => 0.5, 'default' => 0.5],
        'i' => ['type' => 'integer', 'maximum' => 3, 'default' => 3],
        'b' => ['type' => 'boolean', 'default' => false],
    ]));

    expect($schema->toArray()['properties']->s['default'])->toBe('ok')
        ->and($schema->toArray()['properties']->b['default'])->toBeFalse();
    expect(new StringProperty(default: 'x'))->default->toBe('x')
        ->and(new NumberProperty(default: 1))->default->toBe(1)
        ->and(new IntegerProperty(default: 1))->default->toBe(1);
});

it('checks a multi-select answer value by value and by count', function () {
    $schema = ObjectSchema::fromArray(formOf([
        'tags' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 2, 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c']]],
        'titled' => ['type' => 'array', 'items' => ['anyOf' => [['const' => 'x', 'title' => 'X']]]],
        'one' => ['type' => 'string', 'oneOf' => [['const' => 'p', 'title' => 'P']]],
    ], ['tags']));

    expect($schema->problem(['tags' => ['a', 'c'], 'titled' => ['x'], 'one' => 'p']))->toBeNull()
        ->and($schema->problem(['tags' => []]))->toBe('[tags] must have at least 1 items.')
        ->and($schema->problem(['tags' => ['a', 'b', 'c']]))->toBe('[tags] must have at most 2 items.')
        ->and($schema->problem(['tags' => ['a', 'z']]))->toBe('[tags] item 1 must be one of: a, b, c.')
        ->and($schema->problem(['tags' => 'a']))->toBe('[tags] must be a list.')
        ->and($schema->problem(['tags' => ['a'], 'titled' => ['y']]))->toBe('[titled] item 0 must be one of: x.')
        ->and($schema->problem(['tags' => ['a'], 'one' => 'q']))->toBe('[one] must be one of: p.');
});

it('does not take an array of anything else as an elicitation field', function () {
    $nested = new ObjectSchema(['tags' => new ArrayProperty(new StringProperty())]);
    $titledItems = new ObjectSchema(['t' => new ArrayProperty(new EnumProperty(['a'], title: 'T'))]);
    $legacyItems = new ObjectSchema(['t' => new ArrayProperty(new EnumProperty(['a'], ['A']))]);
    $ok = new ObjectSchema(['t' => new ArrayProperty(new EnumProperty(['a']))]);

    expect($nested->isFlat())->toBeFalse()->and($titledItems->isFlat())->toBeFalse()->and($legacyItems->isFlat())->toBeFalse()->and($ok->isFlat())->toBeTrue();
});

it('carries a list of strings as an accepted answer\'s content, over the wire and back', function () {
    $json = (new ElicitResponse(3, ElicitAnswer::accept(['tags' => ['a', 'b'], 'one' => 'p'])))->toJson();
    $wire = json_decode($json, true);
    $back = ElicitResponse::fromReceived(new ReceivedResponse(3, $wire['result']));
    $schema = ObjectSchema::fromArray(formOf(['tags' => ['type' => 'array', 'maxItems' => 2, 'items' => ['type' => 'string', 'enum' => ['a', 'b']]], 'one' => ['type' => 'string', 'enum' => ['p']]]));

    expect($wire['result']['content'])->toBe(['tags' => ['a', 'b'], 'one' => 'p'])
        ->and($back->answer->content)->toBe(['tags' => ['a', 'b'], 'one' => 'p'])
        ->and($schema->problem($back->answer->content))->toBeNull()
        ->and($schema->problem(['tags' => ['a', 'b', 'a']]))->toBe('[tags] must have at most 2 items.');
});
