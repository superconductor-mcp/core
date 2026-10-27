<?php

use Fabricate\NutsAndBolts\ToolDefinition;
use Fabricate\NutsAndBolts\ToolAnnotations;
use Fabricate\NutsAndBolts\ProgressReporter;
use Fabricate\NutsAndBolts\Schema\AudioProperty;
use Fabricate\NutsAndBolts\Schema\StringProperty;
use Fabricate\NutsAndBolts\Messages\Tools\CallToolResponse;
use Fabricate\NutsAndBolts\Messages\Progress\ProgressNotification;
use Fabricate\NutsAndBolts\Messages\Initialize\InitializeResponse;

it('emits tool annotations only when the definition carries them', function () {
    $plain = new ToolDefinition('a', 'A', ['x' => new StringProperty('x')]);
    expect($plain->toArray())->not->toHaveKey('annotations');

    $hinted = new ToolDefinition('b', 'B', annotations: new ToolAnnotations(title: 'Bee', read_only: true, open_world: false));
    expect($hinted->toArray()['annotations'])->toBe(['title' => 'Bee', 'readOnlyHint' => true, 'openWorldHint' => false]);
});

it('carries audio content in a tool result', function () {
    $audio = new AudioProperty(base64_encode('RIFF'), 'audio/wav');
    expect($audio->toArray())->toBe(['type' => 'audio', 'data' => base64_encode('RIFF'), 'mimeType' => 'audio/wav']);
    expect(fn () => new AudioProperty(base64_encode('x'), 'image/png'))->toThrow(InvalidArgumentException::class);

    $decoded = json_decode((new CallToolResponse(1, [$audio]))->toJson(), true);
    expect($decoded['result']['content'][0]['type'])->toBe('audio');
});

it('passes the progress message through the notification and the reporter', function () {
    $sent = [];
    $reporter = new ProgressReporter('tok', function (ProgressNotification $n) use (&$sent): void { $sent[] = $n; });
    $reporter->report(1, 3, 'warming up');
    $reporter->report(2, 3);

    expect($sent[0]->message)->toBe('warming up')
        ->and(json_decode($sent[0]->toJson(), true)['params'])->toBe(['progressToken' => 'tok', 'progress' => 1, 'total' => 3, 'message' => 'warming up'])
        ->and(json_decode($sent[1]->toJson(), true)['params'])->not->toHaveKey('message');

    expect(ProgressNotification::generate(['progressToken' => 1, 'progress' => 0.5, 'message' => 'half']))->message->toBe('half');
    expect(ProgressNotification::generate(['progressToken' => 1, 'progress' => 0.5, 'message' => 7]))->toBeNull();
});

it('encodes an empty completions capability as a JSON object', function () {
    $json = (new InitializeResponse(1, '2025-03-26', ['completions' => [], 'tools' => ['listChanged' => true]], ['name' => 's', 'version' => '1']))->toJson();
    expect($json)->toContain('"completions":{}');
});
