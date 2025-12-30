<?php

use App\Models\TimeslotPreset;
use App\Models\Trainer;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can create a preset with multiple trainers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trainers = Trainer::factory()->count(2)->create();

    $payload = [
        'preset_title' => 'Multi Trainer Preset',
        'title' => 'Group Lesson',
        'capacity' => 5,
        'price' => 50,
        'trainer_ids' => $trainers->pluck('id')->all(),
    ];

    $res = $this->post('/dashboard/timeslots/presets', $payload);
    $res->assertRedirect('/dashboard/timeslots/presets');

    $preset = TimeslotPreset::where('preset_title', 'Multi Trainer Preset')->firstOrFail();
    expect($preset->trainers)->toHaveCount(2);
    expect($preset->trainers->pluck('id')->all())->toEqual($trainers->pluck('id')->all());
});

it('can update a preset trainers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trainers = Trainer::factory()->count(3)->create();
    $preset = TimeslotPreset::factory()->create();
    $preset->trainers()->sync([$trainers[0]->id, $trainers[1]->id]);

    $payload = [
        'preset_title' => $preset->preset_title,
        'title' => $preset->title,
        'capacity' => $preset->capacity,
        'price' => $preset->price,
        'trainer_ids' => [$trainers[1]->id, $trainers[2]->id],
        '_method' => 'put',
    ];

    $res = $this->post("/dashboard/timeslots/presets/{$preset->id}", $payload);
    $res->assertRedirect('/dashboard/timeslots/presets');

    $preset->refresh();
    expect($preset->trainers)->toHaveCount(2);
    expect($preset->trainers->pluck('id')->all())->toContain($trainers[1]->id, $trainers[2]->id);
    expect($preset->trainers->pluck('id')->all())->not->toContain($trainers[0]->id);
});

it('includes trainer_ids in JSON response', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trainers = Trainer::factory()->count(2)->create([
        'title' => 'Senior Instructor',
    ]);
    $preset = TimeslotPreset::factory()->create();
    $preset->trainers()->sync($trainers->pluck('id')->all());

    // JSON show for prefill (expects Accept: application/json)
    $res = $this->getJson("/dashboard/timeslots/presets/{$preset->id}");
    $res->assertOk();
    $data = $res->json();

    expect($data['trainer_ids'])->toBe($trainers->pluck('id')->all());
    expect($data['trainers'])->toHaveCount(2);
    expect($data['trainers'][0])->toHaveKeys(['id', 'name', 'photo_url', 'title']);
    expect($data['trainers'][0]['title'])->not->toBeNull();
});
