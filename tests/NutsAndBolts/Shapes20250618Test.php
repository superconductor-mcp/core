<?php

use Fabricate\NutsAndBolts\Meta;
use Fabricate\NutsAndBolts\LineDecoder;
use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\PromptArgument;
use Fabricate\NutsAndBolts\PromptDefinition;
use Fabricate\NutsAndBolts\ResourceDefinition;
use Fabricate\NutsAndBolts\Schema\ObjectSchema;
use Fabricate\NutsAndBolts\Schema\ArrayProperty;
use Fabricate\NutsAndBolts\Schema\NumberProperty;
use Fabricate\NutsAndBolts\Schema\StringProperty;
use Fabricate\NutsAndBolts\Capabilities;
use Fabricate\NutsAndBolts\Schema\Annotations;
use Fabricate\NutsAndBolts\Schema\TextProperty;
use Fabricate\NutsAndBolts\Schema\ImageProperty;
use Fabricate\NutsAndBolts\Schema\StructuredContent;
use Fabricate\NutsAndBolts\Schema\ResourceLinkProperty;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;
use Fabricate\NutsAndBolts\Messages\Responses\ReceivedResponse;
use Fabricate\NutsAndBolts\Messages\Completion\CompleteRequest;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAction;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitAnswer;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitRequest;
use Fabricate\NutsAndBolts\Messages\Elicitation\ElicitResponse;
use Fabricate\NutsAndBolts\Messages\Errors\InvalidParams;
use Fabricate\NutsAndBolts\Role;
use Fabricate\NutsAndBolts\PromptMessage;
use Fabricate\NutsAndBolts\Schema\ResourceContents;
use Fabricate\NutsAndBolts\Schema\EmbeddedResourceProperty;
use Fabricate\NutsAndBolts\Messages\Prompts\GetPromptResponse;
use Fabricate\NutsAndBolts\Messages\Resources\ReadResourceResponse;

it('puts titles, output schemas, annotations and _meta on definitions only when set', function () {
    $tool = new ToolDefinition('measure', 'Measures', title: 'Measure a part', output_schema: new ObjectSchema(['mm' => new NumberProperty()], ['mm']), meta: ['acme.dev/unit' => 'mm']);
    expect($tool->toArray())
        ->toMatchArray(['name' => 'measure', 'title' => 'Measure a part'])
        ->and(json_encode($tool->toArray()['outputSchema']))->toBe('{"type":"object","properties":{"mm":{"type":"number"}},"required":["mm"]}')
        ->and((array) $tool->toArray()['_meta'])->toBe(['acme.dev/unit' => 'mm'])
        ->and(new ToolDefinition('plain')->toArray())->not->toHaveKeys(['title', 'outputSchema', '_meta']);

    $resource = new ResourceDefinition('cad://part', 'part', title: 'The part', annotations: new Annotations(['user'], 0.5, '2025-01-12T15:00:58Z'));
    expect($resource->toArray())->toMatchArray(['title' => 'The part', 'annotations' => ['audience' => ['user'], 'priority' => 0.5, 'lastModified' => '2025-01-12T15:00:58Z']]);

    $prompt = new PromptDefinition('greet', arguments: [new PromptArgument('who', title: 'Who')], title: 'Greet');
    expect($prompt->toArray()['title'])->toBe('Greet')->and($prompt->toArray()['arguments'][0]['title'])->toBe('Who');
});

it('checks _meta key names and annotation values where they are set', function () {
    expect(Meta::check(['plain' => 1, 'io.acme/x-y.z' => 2, '' => 3]))->toHaveCount(3);
    expect(fn () => Meta::check(['bad key' => 1]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Meta::check(['-x' => 1]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Annotations(['robot']))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Annotations(priority: 2))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Annotations(last_modified: 'yesterday-ish'))->toThrow(InvalidArgumentException::class);
});

it('builds resource links and annotated content items', function () {
    expect(new ResourceLinkProperty('file:///a.rs', 'a.rs', mime_type: 'text/x-rust', annotations: new Annotations(['assistant'], 0.9))->toArray())
        ->toBe(['type' => 'resource_link', 'uri' => 'file:///a.rs', 'name' => 'a.rs', 'mimeType' => 'text/x-rust', 'annotations' => ['audience' => ['assistant'], 'priority' => 0.9]])
        ->and(new TextProperty('hi', new Annotations(['user']))->toArray())->toBe(['type' => 'text', 'text' => 'hi', 'annotations' => ['audience' => ['user']]])
        ->and(new ImageProperty(base64_encode('x'), 'image/png')->toArray())->not->toHaveKey('annotations');
});

it('sends structured content beside a JSON text block, and reads it back', function () {
    $data = new StructuredContent(['mm' => 12.5]);
    $wire = json_decode((new CallToolResponse(7, $data->content(), structured: $data->data))->toJson(), true)['result'];

    expect($wire['structuredContent'])->toBe(['mm' => 12.5])->and($wire['content'][0])->toBe(['type' => 'text', 'text' => '{"mm":12.5}']);

    $back = CallToolResponse::fromReceived(new ReceivedResponse(7, $wire));
    expect($back->structured)->toBe(['mm' => 12.5]);
    expect(fn () => CallToolResponse::fromReceived(new ReceivedResponse(7, ['content' => [], 'structuredContent' => [1, 2]])))->toThrow(UnexpectedValueException::class);
});

it('reads completion context and refuses a malformed one', function () {
    $request = CompleteRequest::generate(1, 'completion/complete', ['ref' => ['type' => 'ref/prompt', 'name' => 'p'], 'argument' => ['name' => 'b', 'value' => 'x'], 'context' => ['arguments' => ['a' => 'py']]]);
    expect($request->context_arguments)->toBe(['a' => 'py'])
        ->and(json_decode($request->toJson(), true)['params']['context'])->toBe(['arguments' => ['a' => 'py']])
        ->and(CompleteRequest::generate(1, 'completion/complete', ['ref' => ['type' => 'ref/prompt', 'name' => 'p'], 'argument' => ['name' => 'b', 'value' => 'x'], 'context' => ['arguments' => ['a' => 3]]]))->toBeInstanceOf(InvalidParams::class);
});

it('knows which capability a method needs, by version', function () {
    expect(Capabilities::requiredBy('tools/call', '2025-06-18'))->toBe(['tools'])
        ->and(Capabilities::requiredBy('resources/subscribe', '2025-06-18'))->toBe(['resources', 'subscribe'])
        ->and(Capabilities::requiredBy('completion/complete', '2024-11-05'))->toBeNull()
        ->and(Capabilities::requiredBy('completion/complete', '2025-03-26'))->toBe(['completions'])
        ->and(Capabilities::requiredBy('ping', '2025-06-18'))->toBeNull()
        ->and(Capabilities::declares(['resources' => ['subscribe' => false]], ['resources', 'subscribe']))->toBeFalse()
        ->and(Capabilities::declares(['resources' => ['subscribe' => true]], ['resources', 'subscribe']))->toBeTrue()
        ->and(Capabilities::declares(['elicitation' => []], ['elicitation']))->toBeTrue();
});

it('accepts only the elicitation schema subset, and checks answers against it', function () {
    $schema = ['type' => 'object', 'properties' => [
        'name' => ['type' => 'string', 'minLength' => 2],
        'email' => ['type' => 'string', 'format' => 'email'],
        'size' => ['type' => 'string', 'enum' => ['s', 'm'], 'enumNames' => ['Small', 'Medium']],
        'count' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
        'ok' => ['type' => 'boolean', 'default' => true],
        'when' => ['type' => 'string', 'format' => 'date-time'],
    ], 'required' => ['name']];

    $read = ObjectSchema::fromArray($schema);
    $refused = function (array $schema): string {
        try {
            ObjectSchema::fromArray($schema);
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        return '';
    };

    expect($read->toArray()['required'])->toBe(['name'])->and(json_encode($read->toArray()['properties']))->toBe(json_encode($schema['properties']))
        ->and($refused(['type' => 'object', 'properties' => ['x' => ['type' => 'array']]]))->toContain('[x]')
        ->and($refused(['type' => 'object', 'properties' => ['x' => ['type' => 'string', 'pattern' => '.']]]))->toContain('pattern')
        ->and($refused(['type' => 'object', 'properties' => [], 'required' => ['ghost']]))->toContain('required')
        ->and($refused(['type' => 'object', 'properties' => ['x' => ['type' => 'string', 'enum' => ['a'], 'enumNames' => ['A', 'B']]]]))->toContain('names')
        ->and($refused(['type' => 'object', 'properties' => ['x' => ['type' => 'integer', 'minimum' => 'one']]]))->toContain('wrong type');

    expect($read->problem(['name' => 'Ada', 'email' => 'ada@example.com', 'size' => 'm', 'count' => 3, 'ok' => false, 'when' => '2025-06-18T10:00:00Z']))->toBeNull()
        ->and($read->problem([]))->toContain('[name] is required')
        ->and($read->problem(['name' => 'A']))->toContain('at least 2')
        ->and($read->problem(['name' => 'Ada', 'email' => 'nope']))->toContain('email')
        ->and($read->problem(['name' => 'Ada', 'size' => 'xl']))->toContain('one of')
        ->and($read->problem(['name' => 'Ada', 'count' => 9]))->toContain('at most 5')
        ->and($read->problem(['name' => 'Ada', 'count' => 2.5]))->toContain('integer')
        ->and($read->problem(['name' => 'Ada', 'count' => 3.0]))->toBeNull()
        ->and($read->problem(['name' => 'Ada', 'when' => '2025-02-30T10:00:00Z']))->toContain('date-time')
        ->and($read->problem(['name' => 'Ada', 'extra' => 1]))->toContain('not in the schema');

    $nested = new ObjectSchema(['tags' => new ArrayProperty(new StringProperty())]);
    expect($nested->isFlat())->toBeFalse()->and($read->isFlat())->toBeTrue()
        ->and($nested->problem(['tags' => ['a', 2]]))->toBe('[tags] item 1 must be a string.')
        ->and(fn () => new ElicitRequest(1, 'x', $nested))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new ObjectSchema(['x' => new StringProperty()], ['y']))->toThrow(InvalidArgumentException::class);
});

it('carries elicitation requests and answers on the wire', function () {
    $line = (new ElicitRequest(4, 'Your name?', new ObjectSchema(['name' => new StringProperty()], ['name'])))->toJson();
    $decoded = LineDecoder::decodeString($line);

    expect($decoded)->toBeInstanceOf(ElicitRequest::class)->and($decoded->requested_schema->required)->toBe(['name'])
        ->and(LineDecoder::decodeString(json_encode(['jsonrpc' => '2.0', 'id' => 5, 'method' => 'elicitation/create', 'params' => ['message' => 'x', 'requestedSchema' => ['type' => 'object', 'properties' => ['n' => ['type' => 'array']]]]])))->toBeInstanceOf(InvalidParams::class);

    expect(json_decode((new ElicitResponse(4, ElicitAnswer::accept(['name' => 'Ada'])))->toJson(), true)['result'])->toBe(['action' => 'accept', 'content' => ['name' => 'Ada']])
        ->and(json_decode((new ElicitResponse(4, ElicitAnswer::decline()))->toJson(), true)['result'])->toBe(['action' => 'decline']);

    $back = ElicitResponse::fromReceived(new ReceivedResponse(4, ['action' => 'cancel', 'content' => ['ignored' => true]]));
    expect($back->answer->action)->toBe(ElicitAction::Cancel)->and($back->answer->content)->toBeNull();
    expect(fn () => ElicitResponse::fromReceived(new ReceivedResponse(4, ['action' => 'maybe'])))->toThrow(UnexpectedValueException::class);
    expect(LineDecoder::isBatch(' [{}]'))->toBeTrue()->and(LineDecoder::isBatch('{}'))->toBeFalse();
});

it('reads received content into the same primitives a server sends', function () {
    $content = [
        ['type' => 'text', 'text' => 'hi', 'annotations' => ['audience' => ['user'], 'priority' => 1]],
        ['type' => 'resource', 'resource' => ['uri' => 'cad://part', 'mimeType' => 'text/plain', 'text' => 'part']],
        ['type' => 'resource_link', 'uri' => 'cad://doc', 'name' => 'doc', 'title' => 'The doc'],
    ];
    $call = CallToolResponse::fromReceived(new ReceivedResponse(1, ['content' => $content]));

    expect($call->content[0])->toBeInstanceOf(TextProperty::class)->and($call->content[0]->annotations->priority)->toBe(1.0)
        ->and($call->content[1])->toBeInstanceOf(EmbeddedResourceProperty::class)->and($call->content[1]->contents->text)->toBe('part')
        ->and($call->content[2])->toBeInstanceOf(ResourceLinkProperty::class)->and($call->content[2]->title)->toBe('The doc')
        ->and(json_decode($call->toJson(), true)['result']['content'][2])->toBe($content[2]);

    $prompt = GetPromptResponse::fromReceived(new ReceivedResponse(2, ['messages' => [['role' => 'assistant', 'content' => ['type' => 'text', 'text' => 'ok']]]]));
    $read = ReadResourceResponse::fromReceived(new ReceivedResponse(3, ['contents' => [['uri' => 'cad://part', 'blob' => base64_encode('x')]]]));

    expect($prompt->messages[0])->toBeInstanceOf(PromptMessage::class)->and($prompt->messages[0]->role)->toBe(Role::Assistant)
        ->and($read->contents[0])->toBeInstanceOf(ResourceContents::class)->and($read->contents[0]->blob)->toBe(base64_encode('x'))
        ->and(fn () => CallToolResponse::fromReceived(new ReceivedResponse(4, ['content' => [['type' => 'video']]])))->toThrow(UnexpectedValueException::class, 'content type')
        ->and(fn () => CallToolResponse::fromReceived(new ReceivedResponse(5, ['content' => [['type' => 'text']]])))->toThrow(UnexpectedValueException::class, 'missing a field')
        ->and(fn () => GetPromptResponse::fromReceived(new ReceivedResponse(6, ['messages' => [['role' => 'system', 'content' => []]]])))->toThrow(UnexpectedValueException::class, 'role')
        ->and(fn () => ReadResourceResponse::fromReceived(new ReceivedResponse(7, ['contents' => [['uri' => 'x']]])))->toThrow(UnexpectedValueException::class, 'exactly one');
});

it('refuses structured data that is not an object, and content that is not primitives', function () {
    expect(fn () => new StructuredContent([1, 2]))->toThrow(InvalidArgumentException::class, 'JSON object')
        ->and(fn () => new StructuredContent(['a' => 1], [['type' => 'text', 'text' => 'raw']]))->toThrow(InvalidArgumentException::class, 'schema primitives')
        ->and(new StructuredContent([])->content()[0]->value)->toBe('{}');
});
